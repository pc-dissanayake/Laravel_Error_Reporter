<?php

namespace Sadpcd\ErrorAlerts\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Sadpcd\ErrorAlerts\Mail\ErrorAlertMailable;
use Sadpcd\ErrorAlerts\Models\ErrorAlert;
use Throwable;

class ErrorAlertManager
{
    protected ErrorClassifier $classifier;
    protected ErrorFingerprint $fingerprinter;
    protected ErrorThrottler $throttler;
    protected ContextCollector $collector;

    public function __construct(
        ErrorClassifier $classifier,
        ErrorFingerprint $fingerprinter,
        ErrorThrottler $throttler,
        ContextCollector $collector
    ) {
        $this->classifier = $classifier;
        $this->fingerprinter = $fingerprinter;
        $this->throttler = $throttler;
        $this->collector = $collector;
    }

    /**
     * Handle an exception and dispatch alerts if eligible.
     *
     * @param Throwable $exception
     * @param string $contextType
     * @param array<string, mixed> $extra
     * @return bool True if an email was sent, false otherwise.
     */
    public function handle(Throwable $exception, string $contextType = 'http', array $extra = []): bool
    {
        try {
            // 1. Should we report this error?
            if (!$this->classifier->shouldReport($exception, $contextType)) {
                return false;
            }

            // 2. Generate unique fingerprint
            $fingerprint = $this->fingerprinter->generate($exception);

            // 3. Flood Protection & Throttling Check
            $throttleResult = $this->throttler->shouldSend($fingerprint);

            // 4. Record to Database (if enabled)
            $this->recordToDatabase($exception, $fingerprint, $contextType, $extra, $throttleResult['occurrences']);

            // 5. If throttled, skip sending email
            if (!$throttleResult['should_send']) {
                return false;
            }

            // 6. Gather rich context (scrubbed)
            $context = $this->collector->collect($exception, $contextType, $extra);

            // 7. Dispatch Email Alert
            $this->sendEmail($exception, $fingerprint, $throttleResult['occurrences'], $context);

            return true;
        } catch (Throwable $e) {
            // Fail-safe: Under NO circumstances should the error alert system crash the host app
            try {
                Log::channel('single')->error("[SADPCD Error Alerts] Failed to process alert: " . $e->getMessage());
            } catch (Throwable) {
                // Completely isolated
            }

            return false;
        }
    }

    /**
     * Send email to recipients using configured mailer.
     */
    protected function sendEmail(Throwable $exception, string $fingerprint, int $occurrences, array $context): void
    {
        $recipients = config('error-alerts.recipients', []);

        if (empty($recipients)) {
            return;
        }

        $mailable = new ErrorAlertMailable(
            exceptionClass: get_class($exception),
            errorMessage: $exception->getMessage(),
            file: $exception->getFile(),
            line: $exception->getLine(),
            code: (string) $exception->getCode(),
            fingerprint: $fingerprint,
            occurrences: $occurrences,
            context: $context
        );

        $mailerName = config('error-alerts.mailer');
        $mail = $mailerName ? Mail::mailer($mailerName) : Mail::mailer();

        if (config('error-alerts.queue.enabled', false)) {
            $mail->to($recipients)->queue($mailable);
        } else {
            $mail->to($recipients)->send($mailable);
        }
    }

    /**
     * Persist or update error record in database for Filament / historical inspection.
     */
    protected function recordToDatabase(
        Throwable $exception,
        string $fingerprint,
        string $contextType,
        array $extra,
        int $occurrences
    ): void {
        if (!config('error-alerts.database.enabled', true)) {
            return;
        }

        try {
            $user = null;
            if (app()->bound('auth') && auth()->check()) {
                $user = auth()->user();
            }

            $request = app()->bound('request') ? app('request') : null;

            $existing = ErrorAlert::where('fingerprint', $fingerprint)->first();

            if ($existing) {
                $existing->increment('occurrences_count');
                $existing->update([
                    'last_seen_at' => now(),
                    // Reopen if it was previously marked resolved
                    'status' => $existing->status === 'resolved' ? 'unresolved' : $existing->status,
                ]);
            } else {
                ErrorAlert::create([
                    'fingerprint'       => $fingerprint,
                    'exception_class'   => get_class($exception),
                    'message'           => substr($exception->getMessage(), 0, 65535),
                    'file'              => $exception->getFile(),
                    'line'              => $exception->getLine(),
                    'code'              => (string) $exception->getCode(),
                    'url'               => $request ? $request->fullUrl() : null,
                    'method'            => $request ? $request->method() : null,
                    'ip_address'        => $request ? $request->ip() : null,
                    'user_agent'        => $request ? substr((string) $request->userAgent(), 0, 500) : null,
                    'user_id'           => $user ? (string) $user->getAuthIdentifier() : null,
                    'user_email'        => $user ? ($user->email ?? null) : null,
                    'context_type'      => $contextType,
                    'context_data'      => !empty($extra) ? $extra : null,
                    'stack_trace'       => substr($exception->getTraceAsString(), 0, 65535),
                    'occurrences_count' => $occurrences,
                    'last_seen_at'      => now(),
                    'status'            => 'unresolved',
                ]);
            }
        } catch (Throwable) {
            // Database might be unavailable (e.g. SQL connection failed error); ignore failure safely
        }
    }
}
