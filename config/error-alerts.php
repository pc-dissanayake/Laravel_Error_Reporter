<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable / Disable Error Alerts
    |--------------------------------------------------------------------------
    |
    | Master switch to activate or deactivate the alerting engine.
    |
    */
    'enabled' => env('ERROR_ALERTS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Alert Recipients
    |--------------------------------------------------------------------------
    |
    | Email addresses that should receive the 500 error notifications.
    | Set ERROR_ALERT_EMAIL in your .env file to enable alerts.
    | If not set or empty, no error alerts will be dispatched.
    |
    | Example: ERROR_ALERT_EMAIL=alerts@yourdomain.com
    |
    */
    'recipients' => array_filter(
        array_map('trim', explode(',', (string) env('ERROR_ALERT_EMAIL', env('ERROR_ALERTS_RECIPIENTS', ''))))
    ),

    'cc' => array_filter(
        array_map('trim', explode(',', env('ERROR_ALERTS_CC', '')))
    ),

    'bcc' => array_filter(
        array_map('trim', explode(',', env('ERROR_ALERTS_BCC', '')))
    ),

    /*
    |--------------------------------------------------------------------------
    | Dedicated Mailer
    |--------------------------------------------------------------------------
    |
    | You can specify a dedicated mailer connection defined in config/mail.php
    | (e.g., 'smtp', 'postmark', 'mailgun', 'failover'). If set to null, it
    | falls back to config('mail.default').
    |
    */
    'mailer' => env('ERROR_ALERTS_MAILER', null),

    /*
    |--------------------------------------------------------------------------
    | Active Environments
    |--------------------------------------------------------------------------
    |
    | List of environments where error alerts should be sent. Useful for
    | preventing test or local development errors from spamming your inbox.
    |
    */
    'environments' => array_filter(
        array_map('trim', explode(',', env('ERROR_ALERTS_ENVIRONMENTS', 'production,staging')))
    ),

    /*
    |--------------------------------------------------------------------------
    | Flood Protection (Throttling)
    |--------------------------------------------------------------------------
    |
    | Prevents email inbox flooding when recurring errors happen in quick succession.
    | Identical errors are grouped by a SHA-256 fingerprint (class + file + line).
    |
    | 'minutes': Duration in minutes to wait before sending another alert for the
    |            exact same error.
    | 'driver':  'auto' (tries default cache store, falls back to disk file on DB/Redis crash).
    |
    */
    'throttle' => [
        'enabled' => env('ERROR_ALERTS_THROTTLE_ENABLED', true),
        'minutes' => (int) env('ERROR_ALERTS_THROTTLE_MINUTES', 30),
        'driver'  => env('ERROR_ALERTS_THROTTLE_DRIVER', 'auto'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker (Global Burst Limiter)
    |--------------------------------------------------------------------------
    |
    | Protects against catastrophic server incidents where dozens of DIFFERENT
    | errors occur simultaneously. If the total number of alerts exceeds this
    | threshold within 1 hour, alerting pauses until the burst subsides.
    |
    */
    'burst_limiter' => [
        'enabled'             => env('ERROR_ALERTS_BURST_ENABLED', true),
        'max_alerts_per_hour' => (int) env('ERROR_ALERTS_BURST_MAX', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Delivery Mode
    |--------------------------------------------------------------------------
    |
    | When false (recommended for fail-safe alerts), emails are dispatched
    | synchronously so that database queue connection failures will not
    | prevent the emergency alert from reaching your inbox.
    |
    */
    'queue' => [
        'enabled'    => env('ERROR_ALERTS_QUEUE', false),
        'connection' => env('ERROR_ALERTS_QUEUE_CONNECTION', null),
        'queue'      => env('ERROR_ALERTS_QUEUE_NAME', 'alerts'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Capture Scope
    |--------------------------------------------------------------------------
    |
    | Toggle which error contexts are monitored for unhandled exceptions.
    |
    */
    'capture' => [
        'http'     => true, // Standard web HTTP requests (500s)
        'inertia'  => true, // Inertia.js / Vite SPA requests
        'livewire' => true, // Laravel Livewire component actions
        'queue'    => true, // Failed background queued jobs
        'console'  => true, // Artisan CLI commands & scheduled crons
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Data Masking (Scrubbing)
    |--------------------------------------------------------------------------
    |
    | Request inputs, query strings, headers, and session keys that match these
    | patterns will be redacted (e.g. replaced with '[REDACTED]') before emailing.
    |
    */
    'scrub_fields' => [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        '_token',
        'api_token',
        'access_token',
        'refresh_token',
        'authorization',
        'secret',
        'key',
        'api_key',
        'card_number',
        'cvv',
        'cvc',
        'credit_card',
        'cookie',
        'x-xsrf-token',
        'x-csrf-token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Exceptions (Do Not Report)
    |--------------------------------------------------------------------------
    |
    | Exceptions that should never trigger an email alert (e.g., 404 Not Found,
    | validation errors, authentication redirects, CSRF expirations).
    |
    */
    'dont_report' => [
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException::class,
        \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException::class,
        \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
        \Illuminate\Validation\ValidationException::class,
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Http\Exceptions\HttpResponseException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Storage & Filament UI Support
    |--------------------------------------------------------------------------
    |
    | When enabled, errors are logged to the database table for review inside
    | the optional Filament Admin Panel. If the database is temporarily
    | unavailable, the alerting engine catches the DB error and still sends email.
    |
    */
    'database' => [
        'enabled' => env('ERROR_ALERTS_DB_LOGGING', true),
        'table'   => 'error_alerts',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Prune Old Errors
    |--------------------------------------------------------------------------
    |
    | Number of days to retain logged errors before they are pruned by
    | the `error-alerts:prune` command.
    |
    */
    'prune_after_days' => 30,

];
