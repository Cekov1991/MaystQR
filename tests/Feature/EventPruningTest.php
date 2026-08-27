<?php

namespace Tests\Feature;

use App\Models\SiteEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The retention window published in the Privacy Policy, enforced.
 *
 * Written in the shape of ScanPruningTest. The most important test here is the
 * plain row-count assertion: if anyone ever adds SoftDeletes to SiteEvent, the
 * command silently stops deleting anything and a published commitment quietly
 * becomes false, with nothing else in the suite noticing.
 */
class EventPruningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['site.event_retention_days' => 90]);
    }

    public function test_it_deletes_events_past_the_retention_window(): void
    {
        SiteEvent::factory()->occurredDaysAgo(120)->count(3)->create();

        $this->artisan('events:prune')->assertSuccessful();

        $this->assertSame(0, SiteEvent::query()->count());
    }

    public function test_it_keeps_events_inside_the_retention_window(): void
    {
        SiteEvent::factory()->occurredDaysAgo(10)->count(2)->create();
        SiteEvent::factory()->occurredDaysAgo(120)->count(3)->create();

        $this->artisan('events:prune')->assertSuccessful();

        $this->assertSame(2, SiteEvent::query()->count());
    }

    public function test_it_follows_the_configured_window(): void
    {
        config(['site.event_retention_days' => 7]);

        SiteEvent::factory()->occurredDaysAgo(10)->create();

        $this->artisan('events:prune')->assertSuccessful();

        $this->assertSame(0, SiteEvent::query()->count());
    }

    public function test_a_dry_run_deletes_nothing(): void
    {
        SiteEvent::factory()->occurredDaysAgo(120)->count(3)->create();

        $this->artisan('events:prune', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(3, SiteEvent::query()->count());
    }

    public function test_it_reports_when_there_is_nothing_to_prune(): void
    {
        SiteEvent::factory()->occurredDaysAgo(1)->create();

        $this->artisan('events:prune')->assertSuccessful();

        $this->assertSame(1, SiteEvent::query()->count());
    }

    /**
     * A misconfigured window must not be read as "delete everything".
     */
    public function test_it_refuses_to_run_with_a_window_below_one_day(): void
    {
        config(['site.event_retention_days' => 0]);

        SiteEvent::factory()->occurredDaysAgo(120)->create();

        $this->artisan('events:prune')->assertFailed();

        $this->assertSame(1, SiteEvent::query()->count());
    }

    /**
     * Larger than one CHUNK, so the batching loop is actually exercised rather
     * than completing in a single pass.
     */
    public function test_it_deletes_more_rows_than_fit_in_one_chunk(): void
    {
        SiteEvent::factory()->occurredDaysAgo(120)->count(1050)->create();

        $this->artisan('events:prune')->assertSuccessful();

        $this->assertSame(0, SiteEvent::query()->count());
    }
}
