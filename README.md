# 🚨 SADPCD Laravel Error Reporter

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sadpcd/laravel-error-reporter.svg?style=flat-square)](https://packagist.org/packages/sadpcd/laravel-error-reporter)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/pc-dissanayake/Laravel_Error_Reporter/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/pc-dissanayake/Laravel_Error_Reporter/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/sadpcd/laravel-error-reporter.svg?style=flat-square)](https://packagist.org/packages/sadpcd/laravel-error-reporter)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.md)

A battle-tested, resilient **500 error reporting and monitoring system** for Laravel (supporting Laravel 10, 11, 12, and 13). Built with **bulletproof failover**, **inbox flood protection**, **Vite & Inertia.js awareness**, **Livewire support**, and an optional **Filament Admin UI component**.

---

## 🌟 Key Features

* **🚨 Real-Time 500 Error Alerts:** Instant, beautifully styled email notifications sent directly to your designated email (or configurable multi-recipient lists) whenever an unhandled 500 error or fatal exception occurs.
* **🛡️ Fail-Safe Architecture (Zero Reliance on Broken Services):** If a 500 error is caused by MySQL or Redis being completely down, the package catches the failure and falls back to a standalone file-based cooldown lock. The alerting system **never** crashes your application or breaks user error responses.
* **🌊 Smart Flood Protection & Burst Limiter:**
  * **Fingerprint Deduplication:** Hashes `sha256(class + file + line + normalized_message)` to prevent duplicate emails for repeating errors.
  * **Configurable Cooldown:** Suppresses duplicate alerts for 30 minutes (configurable), while tracking recurrence counts (*"Occurred 42 times during cooldown"*).
  * **Circuit Breaker:** Automatically throttles if total system errors spike over a global threshold (e.g., max 15 emails/hour) to protect your inbox and SMTP quota during server meltdowns.
* **🔒 Automatic Sensitive Data Scrubbing:** Automatically scrubs passwords, authorization bearer tokens, API secrets, credit cards, and session cookies from request headers and payloads.
* **⚡ 100% Vite & Inertia.js Compatible:** Email templates use self-contained, inlined CSS—completely independent of Vite compilation, Tailwind builds, or local dev servers. Automatically captures Inertia component names and SPA AJAX payloads.
* **🎛️ Laravel Livewire & Queue Support:** Detects Livewire component actions, method parameters, and failed queued background jobs.
* **🖥️ Optional Filament Admin UI Plugin:** Ready-to-use Filament v3/v4 dashboard widget and error management resource with full stack trace viewer and "Mark as Resolved" actions.
* **🛠️ Artisan CLI Tools:** Includes `php artisan error-alerts:test`, `php artisan error-alerts:clear`, and `php artisan error-alerts:prune`.

---

## 📦 Installation

You can install the package via Composer:

```bash
composer require sadpcd/laravel-error-reporter
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="error-alerts-config"
```

*(Optional)* If you want to use the database error log and Filament UI:

```bash
php artisan vendor:publish --tag="error-alerts-migrations"
php artisan migrate
```

---

## ⚙️ Configuration (`.env`)

Simply add your email to `.env` to enable 500 error alerts:

```dotenv
# Your email to receive 500 server error alerts (Required to enable alerts)
ERROR_ALERT_EMAIL=developer@example.com

# Optional settings:
ERROR_ALERTS_ENABLED=true
ERROR_ALERTS_ENVIRONMENTS=production,staging
ERROR_ALERTS_THROTTLE_MINUTES=30
ERROR_ALERTS_BURST_MAX=15
ERROR_ALERTS_QUEUE=false
```

> **Note:** If `ERROR_ALERT_EMAIL` is not set or is empty, the alerting engine is automatically disabled, generating zero overhead and sending no emails.

---

## 🖥️ Filament Admin Panel Integration

If your application uses [Filament](https://filamentphp.com), you can register the **Error Alerts Plugin** in your Panel Provider (e.g. `app/Providers/Filament/AdminPanelProvider.php`):

```php
use Sadpcd\ErrorAlerts\Filament\ErrorAlertsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugins([
            ErrorAlertsPlugin::make()
                ->navigationGroup('Monitoring')
                ->enableWidget(true),
        ]);
}
```

### What You Get in Filament:
1. **500 Error Alerts Resource:** Searchable list of all captured errors, occurrence counts, status badges (*Unresolved / Resolved / Ignored*), and full stack trace inspector.
2. **Dashboard Overview Widget:** Real-time stats card displaying 24h error frequency and top failing exception.
3. **Quick Actions:** "Send Test Alert" and "Clear Cooldowns" directly from the admin panel.

---

## 🛠️ Artisan Commands

### 1. Test Email Delivery
Trigger a simulated 500 error to verify that emails reach your mailbox properly:

```bash
php artisan error-alerts:test

# Or test sending to an explicit email:
php artisan error-alerts:test --email=myemail@example.com
```

### 2. Clear Active Cooldowns
Reset rate-limiting locks and burst counters:

```bash
php artisan error-alerts:clear
```

### 3. Prune Old Database Errors
Clean up historical error logs older than 30 days:

```bash
php artisan error-alerts:prune --days=30
```

---

## 🧪 Running Tests

```bash
composer test
# or
vendor/bin/phpunit
```

---

## 🛡️ Security & Vulnerability Reporting

If you discover any security vulnerabilities, please open an issue or security advisory on GitHub.

---

## 📄 License

The MIT License (MIT). Please see [LICENSE.md](LICENSE.md) for more information.

Copyright © 2026 **SADPCD**.
