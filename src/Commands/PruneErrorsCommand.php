<?php

namespace Sadpcd\ErrorAlerts\Commands;

use Illuminate\Console\Command;
use Sadpcd\ErrorAlerts\Models\ErrorAlert;

class PruneErrorsCommand extends Command
{
    protected $signature = 'error-alerts:prune {--days= : Override retention days}';

    protected $description = 'Prune old resolved and historical error alerts from the database';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('error-alerts.prune_after_days', 30));

        $this->info("Pruning error alerts older than {$days} days...");

        try {
            $cutoff = now()->subDays($days);
            $deleted = ErrorAlert::where('last_seen_at', '<', $cutoff)->delete();

            $this->info("✅ Successfully pruned {$deleted} old error alert records.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("❌ Failed to prune records: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
