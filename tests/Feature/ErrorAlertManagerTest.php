<?php

namespace Sadpcd\ErrorAlerts\Tests\Feature;

use Exception;
use Illuminate\Support\Facades\Mail;
use Sadpcd\ErrorAlerts\Mail\ErrorAlertMailable;
use Sadpcd\ErrorAlerts\Services\ErrorAlertManager;
use Sadpcd\ErrorAlerts\Services\ErrorThrottler;
use Sadpcd\ErrorAlerts\Tests\TestCase;

class ErrorAlertManagerTest extends TestCase
{
    protected ErrorAlertManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = app(ErrorAlertManager::class);
        app(ErrorThrottler::class)->clear();
    }

    public function test_it_dispatches_mailable_when_unhandled_500_occurs(): void
    {
        Mail::fake();

        $exception = new Exception("Critical Database connection down", 500);

        $sent = $this->manager->handle($exception, 'http');

        $this->assertTrue($sent);

        Mail::assertSent(ErrorAlertMailable::class, function (ErrorAlertMailable $mail) {
            return $mail->hasTo('dev@example.com')
                && str_contains($mail->errorMessage, 'Critical Database connection down');
        });
    }

    public function test_it_suppresses_repeat_emails_for_identical_error(): void
    {
        Mail::fake();

        $exception = new Exception("Deadlock detected", 500);

        // First attempt -> sent
        $sentFirst = $this->manager->handle($exception, 'http');
        $this->assertTrue($sentFirst);

        // Second attempt -> throttled
        $sentSecond = $this->manager->handle($exception, 'http');
        $this->assertFalse($sentSecond);

        // Only 1 mail should have been sent
        Mail::assertSent(ErrorAlertMailable::class, 1);
    }
}
