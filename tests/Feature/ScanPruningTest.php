<?php

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\QrCodeScan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Section 7 of the Privacy Policy publishes how long we keep scan records.
 * `scans:prune` is the only thing that makes that statement true, so these tests
 * are guarding a legal claim, not just a cleanup job.
 */
class ScanPruningTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_scans_older_than_the_retention_window(): void
    {
        config(['site.scan_retention_months' => 24]);

        $scan = QrCodeScan::factory()->scannedMonthsAgo(25)->create();

        $this->artisan('scans:prune')->assertSuccessful();

        $this->assertDatabaseMissing('qr_code_scans', ['id' => $scan->id]);
    }

    public function test_it_keeps_scans_inside_the_retention_window(): void
    {
        config(['site.scan_retention_months' => 24]);

        $scan = QrCodeScan::factory()->scannedMonthsAgo(23)->create();

        $this->artisan('scans:prune')->assertSuccessful();

        $this->assertDatabaseHas('qr_code_scans', ['id' => $scan->id]);
    }

    /**
     * The window is not hardcoded anywhere: the command prunes by it and the
     * Privacy Policy renders it. Changing the config must move both.
     */
    public function test_it_follows_the_configured_window(): void
    {
        config(['site.scan_retention_months' => 1]);

        $old = QrCodeScan::factory()->scannedMonthsAgo(2)->create();
        $recent = QrCodeScan::factory()->create();

        $this->artisan('scans:prune')->assertSuccessful();

        $this->assertDatabaseMissing('qr_code_scans', ['id' => $old->id]);
        $this->assertDatabaseHas('qr_code_scans', ['id' => $recent->id]);
    }

    /**
     * Blocked scans are ordinary records covered by the same published period.
     */
    public function test_it_prunes_blocked_scans_too(): void
    {
        config(['site.scan_retention_months' => 24]);

        $blocked = QrCodeScan::factory()->blocked()->scannedMonthsAgo(30)->create();

        $this->artisan('scans:prune')->assertSuccessful();

        $this->assertDatabaseMissing('qr_code_scans', ['id' => $blocked->id]);
    }

    /**
     * scan_count is the lifetime total the subscriber sees. Pruning old detail
     * rows must never make a customer's headline number fall.
     */
    public function test_it_does_not_change_the_lifetime_scan_count(): void
    {
        config(['site.scan_retention_months' => 24]);

        $qrCode = QrCode::factory()->dynamic()->create(['scan_count' => 500]);
        QrCodeScan::factory()->for($qrCode)->scannedMonthsAgo(30)->count(3)->create();

        $this->artisan('scans:prune')->assertSuccessful();

        $this->assertSame(0, $qrCode->scans()->count());
        $this->assertSame(500, $qrCode->fresh()->scan_count);
    }

    public function test_the_dry_run_deletes_nothing(): void
    {
        config(['site.scan_retention_months' => 24]);

        $scan = QrCodeScan::factory()->scannedMonthsAgo(30)->create();

        $this->artisan('scans:prune', ['--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseHas('qr_code_scans', ['id' => $scan->id]);
    }

    public function test_it_reports_when_there_is_nothing_to_prune(): void
    {
        config(['site.scan_retention_months' => 24]);

        QrCodeScan::factory()->create();

        $this->artisan('scans:prune')
            ->expectsOutputToContain('Nothing to prune')
            ->assertSuccessful();

        $this->assertSame(1, QrCodeScan::count());
    }

    /**
     * A misconfigured window of zero would compute a cutoff of "now" and delete
     * every scan in the table, including the one written a second ago. It has to
     * refuse rather than interpret that as "keep nothing".
     */
    public function test_it_refuses_a_window_below_one_month(): void
    {
        config(['site.scan_retention_months' => 0]);

        $scan = QrCodeScan::factory()->scannedMonthsAgo(30)->create();

        $this->artisan('scans:prune')->assertFailed();

        $this->assertDatabaseHas('qr_code_scans', ['id' => $scan->id]);
    }

    /**
     * The delete is chunked so it cannot lock the table against the redirect path.
     * This drives more than one chunk, which also proves the loop terminates —
     * a wrong exit condition here would hang rather than fail.
     */
    public function test_it_deletes_across_more_than_one_chunk(): void
    {
        config(['site.scan_retention_months' => 24]);

        $qrCode = QrCode::factory()->dynamic()->create();
        $stale = now()->subMonths(30);

        $rows = [];
        for ($i = 0; $i < 1200; $i++) {
            $rows[] = [
                'qr_code_id' => $qrCode->id,
                'scanned_at' => $stale,
                'blocked' => false,
            ];
        }
        QrCodeScan::insert($rows);

        QrCodeScan::factory()->for($qrCode)->create();

        $this->assertSame(1201, QrCodeScan::count());

        $this->artisan('scans:prune')->assertSuccessful();

        $this->assertSame(1, QrCodeScan::count());
    }

    /**
     * The published period and the enforced period must be the same number. If
     * this fails, either the policy is lying or the command is.
     */
    public function test_the_privacy_policy_publishes_the_window_the_command_enforces(): void
    {
        config(['site.scan_retention_months' => 18]);

        $this->get('/privacy-policy')
            ->assertOk()
            ->assertSee('18 months');
    }
}
