<?php

namespace Sadpcd\ErrorAlerts\Commands;

use Exception;
use Illuminate\Console\Command;
use Sadpcd\ErrorAlerts\Services\ErrorAlertManager;

class TestAlertCommand extends Command
{
    protected $signature = 'error-reporter:test {--email= : Send to a specific test email} {--force : Bypass active throttling cooldown}';

    protected $aliases = ['error-alerts:test'];

    protected $description = 'Trigger a simulated 500 server error to test SADPCD Error Reporter';

    public function handle(ErrorAlertManager $manager): int
    {
        $this->info("🚀 Preparing simulated 500 error test for SADPCD Error Reporter...");

        if ($this->option('force')) {
            config(['error-reporter.throttle.enabled' => false, 'error-alerts.throttle.enabled' => false]);
            $this->warn("⚡ Force mode enabled: bypassing throttle cooldowns.");
        }

        $recipients = $this->option('email')
            ? [$this->option('email')]
            : (array) config('error-reporter.recipients', config('error-alerts.recipients', []));

        if (empty($recipients)) {
            $this->error("❌ No recipients configured! Set ERROR_ALERT_EMAIL in your .env file.");
            $this->line("Example: ERROR_ALERT_EMAIL=developer@example.com");
            return self::FAILURE;
        }

        $enabled = config('error-reporter.enabled', config('error-alerts.enabled', true));
        $mailer = config('error-reporter.mailer', config('error-alerts.mailer')) ?: config('mail.default', 'default');
        $throttleMins = config('error-reporter.throttle.minutes', config('error-alerts.throttle.minutes', 30));
        $queueEnabled = config('error-reporter.queue.enabled', config('error-alerts.queue.enabled', false));

        $this->table(
            ['Setting', 'Value'],
            [
                ['Enabled', $enabled ? '✅ Yes' : '❌ No'],
                ['Recipients', implode(', ', $recipients)],
                ['Mailer', $mailer],
                ['Environment', app()->environment()],
                ['Throttle Minutes', $throttleMins . ' mins'],
                ['Queue Mode', $queueEnabled ? 'Queued' : 'Sync (Fail-safe)'],
            ]
        );

        $simulatedException = new Exception(
            "🚨 Test 500 Server Error triggered via [artisan error-reporter:test]. If you received this email, SADPCD Error Reporter is working perfectly!",
            500
        );

        // Temporarily override recipients if --email was provided
        if ($this->option('email')) {
            config(['error-reporter.recipients' => $recipients, 'error-alerts.recipients' => $recipients]);
        }

        // Ensure current environment is allowed for test
        config([
            'error-reporter.environments' => [app()->environment()],
            'error-alerts.environments' => [app()->environment()],
        ]);

        $this->line("Dispatching simulated exception through reporter pipeline...");

        $sent = $manager->handle($simulatedException, 'console', [
            'test_mode'    => true,
            'triggered_by' => 'artisan error-reporter:test',
            'host'         => gethostname(),
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
