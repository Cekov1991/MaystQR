<?php

namespace App\Console\Commands;

use App\Enums\SignupSource;
use App\Enums\TrackedEvent;
use App\Models\SiteEvent;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * The funnel, read back.
 *
 * Everything before this phase wrote numbers down; nothing read them. This is
 * the artefact that answers the question the whole event spine was built for —
 * how many strangers who generate a free code end up paying — and it is
 * deliberately a console command rather than a dashboard.
 *
 * A command has no authenticated surface to get wrong, nothing to leak, and no
 * new page to keep in step with the Privacy Policy. It is also honest about who
 * this is for: nobody but us ever needs to see it.
 *
 * Two properties of the underlying data shape every caveat printed below, and
 * they are not defects to be fixed later — they are the privacy design working
 * as intended:
 *
 * 1. The event rows carry no identifier, so these are not stages a cohort moves
 *    through. They are independent counts over the same window. A conversion
 *    rate here is a ratio of two totals, not a proportion of tracked people, and
 *    it can exceed 100% at low volume — one visitor downloading twice inflates
 *    the numerator against a single generation.
 *
 * 2. Four of the counts are browser-reported and therefore forgeable up to the
 *    endpoint's throttle. The registration and subscription figures come from
 *    account rows and are trustworthy; the offer figures are a signal.
 */
class FunnelReport extends Command
{
    protected $signature = 'funnel:report {--days=30 : How many days back to report on}';

    protected $description = 'Report the homepage-to-subscription funnel over a recent window';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->components->error('--days must be at least 1.');

            return self::FAILURE;
        }

        $retention = (int) config('site.event_retention_days');

        $since = now()->subDays($days)->startOfDay();

        $this->components->info(sprintf(
            'Funnel for the %d days since %s.',
            $days,
            $since->toDateString(),
        ));

        /*
         * Asking for a window longer than the retention period is not an error,
         * but reading the answer as though the events were still there would be.
         * The counts simply stop where events:prune got to.
         */
        if ($days > $retention) {
            $this->components->warn(sprintf(
                'Events are pruned after %d days, so anything older than that is gone. '
                .'Account figures still cover the full %d days, which makes the two halves '
                .'of this report cover different windows — compare them with care.',
                $retention,
                $days,
            ));
        }

        $generated = $this->countEvents(TrackedEvent::StaticQrGenerated, $since);
        $downloaded = $this->countEvents(TrackedEvent::QrDownloaded, $since);
        $shown = $this->countEvents(TrackedEvent::OfferShown, $since);
        $dismissed = $this->countEvents(TrackedEvent::OfferDismissed, $since);
        $clicked = $this->countEvents(TrackedEvent::OfferClicked, $since);
        $started = $this->countEvents(TrackedEvent::CheckoutStarted, $since);
        $completed = $this->countEvents(TrackedEvent::CheckoutCompleted, $since);
        $abandoned = $this->countEvents(TrackedEvent::CheckoutAbandoned, $since);

        $this->newLine();
        $this->line('  <options=bold>Anonymous counts</> <fg=gray>— browser-reported in part, see below</>');
        $this->table(
            ['Stage', 'Count', 'Of previous'],
            [
                ['Codes generated', $generated, '—'],
                ['Codes downloaded', $downloaded, $this->rate($downloaded, $generated)],
                ['Offer shown', $shown, $this->rate($shown, $downloaded)],
                ['Offer clicked', $clicked, $this->rate($clicked, $shown)],
                ['Offer dismissed', $dismissed, $this->rate($dismissed, $shown)],
                ['Checkout opened', $started, '—'],
                ['Checkout completed', $completed, $this->rate($completed, $started)],
                ['Checkout abandoned', $abandoned, $this->rate($abandoned, $started)],
            ],
        );

        $this->registrationTable($since);

        $this->newLine();
        $this->line('<fg=gray>  The offer figures are reported by browsers and are forgeable up to the</>');
        $this->line('<fg=gray>  endpoint throttle. Registration and subscription figures come from account</>');
        $this->line('<fg=gray>  rows. No row here identifies anyone, so these are independent totals over</>');
        $this->line('<fg=gray>  the same window rather than one cohort moving through stages — a rate can</>');
        $this->line('<fg=gray>  exceed one hundred per cent at low volume.</>');

        return self::SUCCESS;
    }

    /**
     * Registrations by the link that produced them, and how many of each went on
     * to pay. This half is per-account and therefore exact.
     *
     * `signup_source` is null for most accounts, which is not a gap to be filled
     * — someone who arrived directly is genuinely unattributed — so the untagged
     * row is printed rather than omitted, to keep the column honest as a total.
     */
    private function registrationTable(Carbon $since): void
    {
        $rows = [];

        foreach ([...SignupSource::cases(), null] as $source) {
            $query = User::query()
                ->where('created_at', '>=', $since)
                ->where(
                    fn ($q) => $source === null
                        ? $q->whereNull('signup_source')
                        : $q->where('signup_source', $source->value),
                );

            $registered = (clone $query)->count();

            if ($registered === 0 && $source !== null) {
                $rows[] = [$source->value, 0, 0, '—'];

                continue;
            }

            $subscribed = (clone $query)->whereHas('subscriptions')->count();

            $rows[] = [
                $source->value ?? '(untagged)',
                $registered,
                $subscribed,
                $this->rate($subscribed, $registered),
            ];
        }

        $this->newLine();
        $this->line('  <options=bold>Registrations by source</> <fg=gray>— from account rows, exact</>');
        $this->table(['Source', 'Registered', 'Subscribed', 'Conversion'], $rows);
    }

    private function countEvents(TrackedEvent $event, Carbon $since): int
    {
        return SiteEvent::query()->named($event)->since($since)->count();
    }

    /**
     * A percentage, or an em dash when the denominator is zero.
     *
     * Zero over zero is not 0% and printing it as such invents a finding out of
     * an empty window, which is the single most likely way this report gets
     * misread on its first run.
     */
    private function rate(int $part, int $whole): string
    {
        return $whole === 0 ? '—' : round($part / $whole * 100, 1).'%';
    }
}
