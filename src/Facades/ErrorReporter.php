<?php

namespace Sadpcd\ErrorReporter\Facades;

use Illuminate\Support\Facades\Facade;
use Sadpcd\ErrorAlerts\Services\ErrorAlertManager;

/**
 * @method static bool handle(\Throwable $exception, string $contextType = 'http', array $extra = [])
 * @see \Sadpcd\ErrorAlerts\Services\ErrorAlertManager
 */
class ErrorReporter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ErrorAlertManager::class;
    }
}
