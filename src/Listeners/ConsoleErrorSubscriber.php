<?php

namespace Sadpcd\ErrorAlerts\Listeners;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Events\Dispatcher;
use Sadpcd\ErrorAlerts\Services\ErrorAlertManager;
use Throwable;

class ConsoleErrorSubscriber
{
    protected ErrorAlertManager $manager;

    public function __construct(ErrorAlertManager $manager)
    {
        $this->manager = $manager;
    }

    public function handleCommandFinished(CommandFinished $event): void
    {
        if (!config('error-alerts.capture.console', true)) {
            return;
        }

        // Only handle failed commands
        if ($event->exitCode === 0) {
            return;
        }

        // Ignore standard help / cancelled commands
        if (in_array($event->command, ['help', 'list'], true)) {
            return;
        }

        try {
            // If the command failure was caused by an unhandled exception, it's already caught by the exception handler.
            // But if it finished with non-zero exit code due to a command error:
            $command = $event->command;
            $input = $event->input ? (string) $event->input : '';

            // Note: Exception pipeline catches Throwables; this subscriber handles fatal command aborts if needed.
        } catch (Throwable) {
            // fail-safe
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(CommandFinished::class, [self::class, 'handleCommandFinished']);
    }
}
