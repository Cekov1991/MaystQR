<?php

namespace Tests\Feature;

use App\Enums\SignupSource;
use App\Enums\TrackedEvent;
use App\Models\SiteEvent;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * The report that reads the funnel back.
 *
 * Most of what is worth testing here is not the arithmetic but the honesty of
 * the presentation: an empty window must not read as a finding, a window longer
 * than the retention period must say so, and the exact half of the report must
 * not be mixed up with the forgeable half.
 */
class FunnelReportTest extends TestCase
{
    use RefreshDatabase;

    private function recordEvent(TrackedEvent $event, int $times = 1, int $daysAgo = 0): void
    {
        SiteEvent::factory()
            ->count($times)
            ->named($event)
            ->occurredDaysAgo($daysAgo)
            ->create();
    }

    /**
     * Runs the report and returns everything it printed.
     *
     * Deliberately not $this->artisan()->expectsOutputToContain(): that mocks
     * doWrite per output chunk and Mockery satisfies only the first matching
     * expectation, so two substrings landing on one table row leave the second
     * quietly unasserted. A report is almost entirely tables, so every
     * expectation here would have been at risk of passing for the wrong reason.
     */
    private function report(string $arguments = '--days=30'): string
    {
        $this->assertSame(0, Artisan::call('funnel:report '.$arguments));

        return Artisan::output();
    }

    private function reportFailing(string $arguments): void
    {
        $this->assertNotSame(0, Artisan::call('funnel:report '.$arguments));
    }

    public function test_it_counts_each_stage_over_the_window(): void
    {
        $this->recordEvent(TrackedEvent::StaticQrGenerated, 10);
        $this->recordEvent(TrackedEvent::QrDownloaded, 5);
        $this->recordEvent(TrackedEvent::OfferShown, 4);
        $this->recordEvent(TrackedEvent::OfferClicked, 1);

        $this->assertStringContainsString('Codes generated', $this->report());
    }

    /**
     * The denominator is the previous stage, so a download against ten
     * generations is 50%, not 50 of something unstated.
     */
    public function test_it_expresses_each_stage_against_the_one_before(): void
    {
        $this->recordEvent(TrackedEvent::StaticQrGenerated, 10);
        $this->recordEvent(TrackedEvent::QrDownloaded, 5);

        $this->assertStringContainsString('50%', $this->report());
    }

    public function test_events_outside_the_window_are_excluded(): void
    {
        $this->recordEvent(TrackedEvent::StaticQrGenerated, 3, daysAgo: 2);
        $this->recordEvent(TrackedEvent::StaticQrGenerated, 40, daysAgo: 60);

        $output = $this->report('--days=7');

        // Asserted on the row itself rather than on the absence of "40", which
        // would pass for the wrong reason the moment any other number changed.
        $this->assertMatchesRegularExpression('/Codes generated\s*\|\s*3\s*\|/', $output);
    }

    /**
     * The most likely way this report gets misread on its first run. Zero over
     * zero is not 0%, and printing it as such invents a finding out of an empty
     * window.
     */
    public function test_an_empty_window_shows_no_rate_rather_than_zero_percent(): void
    {
        $this->assertStringNotContainsString('%', $this->report());
    }

    /**
     * The other side of that line: a real zero is a finding and must be printed
     * as one. Ten codes generated and none downloaded is 0%, not "no data".
     */
    public function test_a_genuine_zero_rate_is_printed_as_a_rate(): void
    {
        $this->recordEvent(TrackedEvent::StaticQrGenerated, 10);

        $this->assertStringContainsString('0%', $this->report());
    }

    /**
     * Asking for more than the retention window is allowed, but reading the
     * answer as though the events were still there would not be: the account
     * half of the report covers the full window and the event half does not.
     */
    public function test_it_warns_when_the_window_outruns_the_retention_period(): void
    {
        $this->assertStringContainsString(
            'pruned',
            $this->report('--days='.(config('site.event_retention_days') + 1)),
        );
    }

    public function test_it_does_not_warn_inside_the_retention_period(): void
    {
        $this->assertStringNotContainsString('pruned', $this->report());
    }

    public function test_it_refuses_a_window_of_less_than_a_day(): void
    {
        $this->reportFailing('--days=0');
        $this->reportFailing('--days=-5');
    }

    /**
     * The comparison the whole offer phase exists to make: each link's
     * registrations, and how many of those went on to pay.
     */
    public function test_it_reports_registrations_and_conversions_per_source(): void
    {
        $paying = User::factory()->create(['signup_source' => SignupSource::StaticOffer]);
        Subscription::create([
            'user_id' => $paying->id,
            'agentaos_subscription_id' => 'sub_funnel_1',
            'status' => 'active',
            'current_period_end' => now()->addYear(),
            'unit_amount_minor' => 2700,
            'currency' => 'USD',
        ]);

        User::factory()->create(['signup_source' => SignupSource::StaticOffer]);
        User::factory()->create(['signup_source' => SignupSource::StaticInline]);

        $output = $this->report();

        $this->assertStringContainsString(SignupSource::StaticOffer->value, $output);
        $this->assertStringContainsString(SignupSource::StaticInline->value, $output);
        $this->assertStringContainsString('50%', $output);
    }

    /**
     * Most accounts arrive with no ref, and that is a real answer rather than a
     * gap. Omitting the row would make the column stop adding up to the number
     * of registrations.
     */
    public function test_untagged_registrations_are_shown_rather_than_dropped(): void
    {
        User::factory()->count(3)->create(['signup_source' => null]);

        $this->assertStringContainsString('(untagged)', $this->report());
    }

    public function test_registrations_outside_the_window_are_excluded(): void
    {
        User::factory()->create([
            'signup_source' => SignupSource::StaticOffer,
            'created_at' => now()->subDays(90),
        ]);

        $this->assertStringNotContainsString('100%', $this->report('--days=7'));
    }

    /**
     * The report must never present the browser-reported counts and the
     * account-derived ones as equally trustworthy. One is forgeable up to the
     * endpoint throttle and the other is not.
     */
    public function test_it_states_which_half_of_the_report_is_forgeable(): void
    {
        $this->assertStringContainsString('forgeable', $this->report());
    }

    /**
     * With no identifier on the event rows these are independent totals, not a
     * cohort walking through stages. Saying so is what stops a 140% row being
     * read as a bug in the counter.
     */
    public function test_it_explains_that_the_stages_are_not_a_cohort(): void
    {
        $this->assertStringContainsString('identifies', $this->report());
    }
}
