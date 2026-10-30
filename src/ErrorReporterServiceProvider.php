<?php

namespace Sadpcd\ErrorReporter;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Sadpcd\ErrorAlerts\Commands\ClearThrottlesCommand;
use Sadpcd\ErrorAlerts\Commands\PruneErrorsCommand;
use Sadpcd\ErrorAlerts\Commands\TestAlertCommand;
use Sadpcd\ErrorAlerts\Listeners\ConsoleErrorSubscriber;
use Sadpcd\ErrorAlerts\Listeners\LivewireErrorSubscriber;
use Sadpcd\ErrorAlerts\Listeners\QueueErrorSubscriber;
use Sadpcd\ErrorAlerts\Services\ContextCollector;
use Sadpcd\ErrorAlerts\Services\ErrorAlertManager;
use Sadpcd\ErrorAlerts\Services\ErrorClassifier;
use Sadpcd\ErrorAlerts\Services\ErrorFingerprint;
use Sadpcd\ErrorAlerts\Services\ErrorThrottler;
use Sadpcd\ErrorAlerts\Services\SensitiveDataScrubber;
use Throwable;

class ErrorReporterServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $configFile = file_exists(__DIR__ . '/../config/error-reporter.php')
            ? __DIR__ . '/../config/error-reporter.php'
            : __DIR__ . '/../config/error-alerts.php';

        $this->mergeConfigFrom($configFile, 'error-reporter');
        $this->mergeConfigFrom($configFile, 'error-alerts');

        $this->app->singleton(ErrorClassifier::class);
        $this->app->singleton(ErrorFingerprint::class);
        $this->app->singleton(ErrorThrottler::class);
        $this->app->singleton(SensitiveDataScrubber::class, fn () => new SensitiveDataScrubber());
        $this->app->singleton(ContextCollector::class);
        $this->app->singleton(ErrorAlertManager::class);
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $configFile = file_exists(__DIR__ . '/../config/error-reporter.php')
            ? __DIR__ . '/../config/error-reporter.php'
            : __DIR__ . '/../config/error-alerts.php';

        // 1. Publishing assets & migrations
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $configFile => config_path('error-reporter.php'),
            ], 'error-reporter-config');

            $this->publishes([
                $configFile => config_path('error-alerts.php'),
            ], 'error-alerts-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/error-reporter'),
            ], 'error-reporter-views');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/error-alerts'),
            ], 'error-alerts-views');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'error-reporter-migrations');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'error-alerts-migrations');

            $this->commands([
                TestAlertCommand::class,
                ClearThrottlesCommand::class,
                PruneErrorsCommand::class,
            ]);
        }

        // 2. Load Views & Migrations
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'error-reporter');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'error-alerts');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // 3. Register Global Exception Pipeline Hook
        $this->registerExceptionHandlerHook();

        // 4. Register Event Subscribers (Queue, CLI, Livewire)
        $this->registerSubscribers();
    }

    /**
     * Hook cleanly into Laravel's exception reporting pipeline.
     */
    protected function registerExceptionHandlerHook(): void
    {
        try {
            if ($this->app->bound(ExceptionHandler::class)) {
                /** @var ExceptionHandler $handler */
                $handler = $this->app->make(ExceptionHandler::class);

                if (method_exists($handler, 'reportable')) {
                    $handler->reportable(function (Throwable $e) {
                        $this->app->make(ErrorAlertManager::class)->handle($e, 'http');
                    });
                }
            }
        } catch (Throwable) {
            // Failsafe: never crash during service provider boot
        }
    }

    /**
     * Register listeners for Queue, Console, and Livewire.
     */
    protected function registerSubscribers(): void
    {
        try {
            /** @var Dispatcher $events */
            $events = $this->app->make('events');

            $captureQueue = config('error-reporter.capture.queue', config('error-alerts.capture.queue', true));
            if ($captureQueue) {
                (new QueueErrorSubscriber($this->app->make(ErrorAlertManager::class)))->subscribe($events);
            }

            $captureConsole = config('error-reporter.capture.console', config('error-alerts.capture.console', true));
            if ($captureConsole) {
                (new ConsoleErrorSubscriber($this->app->make(ErrorAlertManager::class)))->subscribe($events);
            }

            $captureLivewire = config('error-reporter.capture.livewire', config('error-alerts.capture.livewire', true));
            if ($captureLivewire) {
                (new LivewireErrorSubscriber($this->app->make(ErrorAlertManager::class)))->register();
            }
        } catch (Throwable) {
            // Failsafe
        }
    }
}
