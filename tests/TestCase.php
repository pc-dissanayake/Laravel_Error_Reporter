<?php

namespace Sadpcd\ErrorAlerts\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Sadpcd\ErrorAlerts\ErrorAlertServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ErrorAlertServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.name', 'SADPCD Test App');
        $app['config']->set('app.env', 'testing');
        $app['config']->set('mail.default', 'array');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('error-alerts.enabled', true);
        $app['config']->set('error-alerts.environments', ['testing']);
        $app['config']->set('error-alerts.recipients', ['sadpc.web@gmail.com']);
        $app['config']->set('error-alerts.throttle.enabled', true);
        $app['config']->set('error-alerts.throttle.minutes', 15);
    }
}
