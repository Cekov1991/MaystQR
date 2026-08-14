<?php

namespace App\Console\Commands;

use App\Models\QrCodeScan;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Deletes QR code scan records older than the published retention window.
 *
 * This command is the only thing that makes section 7 of the Privacy Policy true.
 * That section tells people who scanned a code how long we keep the record, and a
 * retention period nothing enforces is the same class of false claim as the Google
 * Analytics section we deleted — a statement about the site that is not so.
 *
 * The rows describe strangers with no account, so a bounded window is also the
 * data-minimisation position: the analytics value of a two-year-old scan is
 * negligible and the table is otherwise unbounded.
 *
 * `qr_codes.scan_count` is deliberately left alone. It is the lifetime total the
 * subscriber sees, incremented independently on the request path, and pruning old
 * detail rows must not make a customer's headline number fall. It looks like an
 * inconsistency until you know that.
 *
 * Deletes here are real: the scans migration declares softDeletes() but
 * App\Models\QrCodeScan does not use the SoftDeletes trait. If anyone ever adds
 * that trait, this command silently stops deleting anything and the retention
 * period published in the Privacy Policy quietly becomes false. ScanPruningTest
 * asserts the row count actually drops, which is what would catch it.
 */
class PruneScans extends Command
{
    protected $signature = 'scans:prune {--dry-run : Report what would be deleted without deleting it}';

    protected $description = 'Delete QR code scan records past the published retention window';

    /**
     * Rows per DELETE.
     *
     * A single unbounded DELETE across this table would hold locks while every
     * scan of every dynamic code waits behind it — `/q/{shortUrl}` writes here on
     * the request path, and it is the one query that must never be slow.
     */
    private const CHUNK = 1000;

    public function handle(): int
    {
        $cutoff = $this->cutoff();
        $months = $this->retentionMonths();

        if ($months < 1) {
            $this->components->error(
                'site.scan_retention_months must be at least 1; refusing to prune.'
            );

            return self::FAILURE;
        }

        $expired = $this->expiredQuery($cutoff)->count();

        if ($expired === 0) {
            $this->components->info(sprintf(
                'Nothing to prune. No scans older than %s (%d months).',
                $cutoff->toDateString(),
                $months,
            ));

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->components->warn(sprintf(
                'Dry run: %d scans older than %s (%d months) would be deleted.',
                $expired,
                $cutoff->toDateString(),
                $months,
            ));

            return self::SUCCESS;
        }

        $deleted = 0;

        do {
            $batch = $this->expiredQuery($cutoff)->limit(self::CHUNK)->delete();
            $deleted += $batch;
        } while ($batch > 0);

        $this->components->info(sprintf(
            'Pruned %d scans older than %s (%d months).',
            $deleted,
            $cutoff->toDateString(),
            $months,
        ));

        return self::SUCCESS;
    }

    /**
     * Blocked scans are ordinary scan records and are covered by the same
     * published retention period, so they are pruned alongside the rest.
     */
    /** @return Builder<QrCodeScan> */
    private function expiredQuery(Carbon $cutoff): Builder
    {
        return QrCodeScan::query()->where('scanned_at', '<', $cutoff);
    }

    private function cutoff(): Carbon
    {
        return now()->subMonths($this->retentionMonths())->startOfDay();
    }

    private function retentionMonths(): int
    {
        return (int) config('site.scan_retention_months');
    }
}
