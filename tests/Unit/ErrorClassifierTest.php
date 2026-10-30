<?php

namespace Sadpcd\ErrorAlerts\Tests\Unit;

use Exception;
use Illuminate\Validation\ValidationException;
use Sadpcd\ErrorAlerts\Services\ErrorClassifier;
use Sadpcd\ErrorAlerts\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ErrorClassifierTest extends TestCase
{
    protected ErrorClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classifier = app(ErrorClassifier::class);
    }

    public function test_it_reports_standard_500_exceptions(): void
    {
        $exception = new Exception("Database connection failure", 500);
        $this->assertTrue($this->classifier->shouldReport($exception));
    }

    public function test_it_ignores_404_not_found_exceptions(): void
    {
        $exception = new NotFoundHttpException("Page not found");
        $this->assertFalse($this->classifier->shouldReport($exception));
    }

    public function test_it_ignores_validation_exceptions(): void
    {
        $exception = ValidationException::withMessages(['email' => 'Invalid email']);
        $this->assertFalse($this->classifier->shouldReport($exception));
    }

    public function test_it_respects_global_disabled_setting(): void
    {
        config(['error-alerts.enabled' => false]);
        $exception = new Exception("Critical crash", 500);

        $this->assertFalse($this->classifier->shouldReport($exception));
    }

    public function test_it_filters_unmatched_environments(): void
    {
        config(['error-alerts.environments' => ['production']]); // current is 'testing'
        $exception = new Exception("Critical crash", 500);

        $this->assertFalse($this->classifier->shouldReport($exception));
    }

    public function test_it_ignores_when_no_email_is_configured_in_env(): void
    {
        config(['error-alerts.recipients' => []]);
        $exception = new Exception("Server crash", 500);

        $this->assertFalse($this->classifier->shouldReport($exception));
    }

    public function test_it_reports_when_email_is_configured(): void
    {
        config(['error-alerts.recipients' => ['sadpc.web@gmail.com']]);
        $exception = new Exception("Server crash", 500);

        $this->assertTrue($this->classifier->shouldReport($exception));
    }
}
