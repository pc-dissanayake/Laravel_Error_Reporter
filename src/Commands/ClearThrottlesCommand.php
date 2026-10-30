<?php

namespace Sadpcd\ErrorAlerts\Commands;

use Illuminate\Console\Command;
use Sadpcd\ErrorAlerts\Services\ErrorThrottler;

class ClearThrottlesCommand extends Command
{
    protected $signature = 'error-alerts:clear';

    protected $description = 'Reset and flush all active error cooldown locks and burst limit counters';

    public function handle(ErrorThrottler $throttler): int
    {
        $this->info("Flushing active error alerts cooldowns and locks...");

        $throttler->clear();

        $this->info("✅ All error alert cooldowns and rate-limit counters have been cleared.");

        return self::SUCCESS;
    }
}
