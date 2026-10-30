<?php

namespace Sadpcd\ErrorAlerts\Services;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ErrorClassifier
{
    /**
     * Determine if the exception should trigger an alert.
     */
    public function shouldReport(Throwable $exception, string $contextType = 'http'): bool
    {
        // 1. Check global master switch
        if (!config('error-alerts.enabled', true)) {
            return false;
        }

        // 2. Check if recipients are configured (only alert if an email is set in .env)
        $recipients = config('error-alerts.recipients', []);
        if (empty($recipients)) {
            return false;
        }

        // 3. Check environment match
        $activeEnvironments = config('error-alerts.environments', ['production', 'staging']);
        $currentEnvironment = app()->environment();

        if (!empty($activeEnvironments) && !in_array($currentEnvironment, $activeEnvironments, true)) {
            return false;
        }

        // 4. Check context switch (http, livewire, inertia, queue, console)
        if (!config("error-alerts.capture.{$contextType}", true)) {
            return false;
        }

        // 5. Check status code if this is an HTTP exception (ignore < 500 like 404, 403, 422)
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            if ($statusCode < 500) {
                return false;
            }
        }

        // 6. Check explicitly ignored exception classes
        $dontReport = config('error-alerts.dont_report', []);
        foreach ($dontReport as $ignoredClass) {
            if ($exception instanceof $ignoredClass) {
                return false;
            }
        }

        return true;
    }
}
