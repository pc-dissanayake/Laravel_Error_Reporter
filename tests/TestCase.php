<?php

namespace Sadpcd\ErrorAlerts\Tests;

use Sadpcd\ErrorAlerts\ErrorAlertServiceProvider;

if (class_exists(\Orchestra\Testbench\TestCase::class)) {
    abstract class BaseTestAdapter extends \Orchestra\Testbench\TestCase {}
} else {
    abstract class BaseTestAdapter extends \Illuminate\Foundation\Testing\TestCase {}
}

abstract class TestCase extends BaseTestAdapter
{
    protected function getPackageProviders($app): array
    {
        return [
            ErrorAlertServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.name'                  => 'SADPCD Test App',
            'app.env'                   => 'testing',
            'mail.default'              => 'array',
            'cache.default'             => 'array',
            'error-alerts.enabled'      => true,
            'error-alerts.environments' => ['testing'],
            'error-alerts.recipients'   => ['dev@example.com'],
            'error-alerts.throttle.enabled' => true,
            'error-alerts.throttle.minutes' => 15,
        ]);
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.name', 'SADPCD Test App');
        $app['config']->set('app.env', 'testing');
        $app['config']->set('mail.default', 'array');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('error-alerts.enabled', true);
        $app['config']->set('error-alerts.environments', ['testing']);
        $app['config']->set('error-alerts.recipients', ['dev@example.com']);
        $app['config']->set('error-alerts.throttle.enabled', true);
        $app['config']->set('error-alerts.throttle.minutes', 15);
    }
}
