<?php

namespace Sadpcd\ErrorAlerts\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ErrorAlertMailable extends Mailable
{
    use Queueable, SerializesModels;

    public string $exceptionClass;
    public string $errorMessage;
    public string $file;
    public int $line;
    public ?string $code;
    public string $fingerprint;
    public int $occurrences;
    public array $context;
    public string $timestamp;

    public function __construct(
        string $exceptionClass,
        string $errorMessage,
        string $file,
        int $line,
        ?string $code,
        string $fingerprint,
        int $occurrences,
        array $context
    ) {
        $this->exceptionClass = $exceptionClass;
        $this->errorMessage = $errorMessage;
        $this->file = $file;
        $this->line = $line;
        $this->code = $code;
        $this->fingerprint = $fingerprint;
        $this->occurrences = $occurrences;
        $this->context = $context;
        $this->timestamp = now()->toDateTimeString();

        // Configure queue connection if requested
        if (config('error-alerts.queue.enabled', false)) {
            $this->onConnection(config('error-alerts.queue.connection'));
            $this->onQueue(config('error-alerts.queue.queue', 'alerts'));
        }
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $appName = config('app.name', 'Laravel');
        $env = strtoupper(app()->environment());
        $shortClass = class_basename($this->exceptionClass);

        $recurrence = $this->occurrences > 1 ? " ({$this->occurrences}x)" : '';
        $subject = "[🚨 500 ERROR{$recurrence}] {$appName} [{$env}] - {$shortClass}";

        // Configure CC / BCC
        $cc = config('error-alerts.cc', []);
        $bcc = config('error-alerts.bcc', []);

        if (!empty($cc)) {
            $this->cc($cc);
        }
        if (!empty($bcc)) {
            $this->bcc($bcc);
        }

        return $this->subject($subject)
            ->view('error-alerts::emails.error-alert')
            ->text('error-alerts::emails.error-alert-text');
    }
}
