<?php

namespace Sadpcd\ErrorAlerts\Commands;

use Illuminate\Console\Command;
use Sadpcd\ErrorAlerts\Models\ErrorAlert;

class PruneErrorsCommand extends Command
{
    protected $signature = 'error-reporter:prune {--days= : Override retention days}';

    protected $aliases = ['error-alerts:prune'];

    protected $description = 'Prune old resolved and historical error reports from the database';

    public function handle(): int
    {
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : (int) config('error-reporter.prune_after_days', config('error-alerts.prune_after_days', 30));

        $this->info("Pruning error reports older than {$days} days...");

        try {
            $cutoff = now()->subDays($days);
            $deleted = ErrorAlert::where('last_seen_at', '<', $cutoff)->delete();

            $this->info("✅ Successfully pruned {$deleted} old error report records.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("❌ Failed to prune records: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
