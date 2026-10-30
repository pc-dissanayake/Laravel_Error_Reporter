<?php

namespace Sadpcd\ErrorAlerts\Commands;

use Exception;
use Illuminate\Console\Command;
use Sadpcd\ErrorAlerts\Services\ErrorAlertManager;

class TestAlertCommand extends Command
{
    protected $signature = 'error-alerts:test {--email= : Send to a specific test email}';

    protected $description = 'Trigger a simulated 500 server error to test SADPCD Error Alerts';

    public function handle(ErrorAlertManager $manager): int
    {
        $this->info("🚀 Preparing simulated 500 error test for SADPCD Error Alerts...");

        $recipients = $this->option('email')
            ? [$this->option('email')]
            : (array) config('error-alerts.recipients', ['sadpc.web@gmail.com']);

        if (empty($recipients)) {
            $this->error("❌ No recipients configured! Set ERROR_ALERTS_RECIPIENTS in .env");
            return self::FAILURE;
        }

        $this->table(
            ['Setting', 'Value'],
            [
                ['Enabled', config('error-alerts.enabled') ? '✅ Yes' : '❌ No'],
                ['Recipients', implode(', ', $recipients)],
                ['Mailer', config('error-alerts.mailer') ?: config('mail.default', 'default')],
                ['Environment', app()->environment()],
                ['Throttle Minutes', config('error-alerts.throttle.minutes') . ' mins'],
                ['Queue Mode', config('error-alerts.queue.enabled') ? 'Queued' : 'Sync (Fail-safe)'],
            ]
        );

        $simulatedException = new Exception(
            "🚨 Test 500 Server Error triggered via [artisan error-alerts:test]. If you received this email, SADPCD Error Alerts is working perfectly!",
            500
        );

        // Temporarily override recipients if --email was provided
        if ($this->option('email')) {
            config(['error-alerts.recipients' => $recipients]);
        }

        // Ensure current environment is allowed for test
        config(['error-alerts.environments' => [app()->environment()]]);

        $this->line("Dispatching simulated exception through alert pipeline...");

        $sent = $manager->handle($simulatedException, 'console', [
            'test_mode'  => true,
            'triggered_by' => 'artisan error-alerts:test',
            'host'       => gethostname(),
        ]);

        if ($sent) {
            $this->info("✅ Success! Simulated 500 alert was processed and sent to: " . implode(', ', $recipients));
            return self::SUCCESS;
        } else {
            $this->warn("⚠️ Alert was not sent. Check if the error was throttled, the environment is blocked, or mail configuration is failing.");
            return self::FAILURE;
        }
    }
}
