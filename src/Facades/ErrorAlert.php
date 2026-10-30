<?php

namespace Sadpcd\ErrorAlerts\Facades;

use Illuminate\Support\Facades\Facade;
use Sadpcd\ErrorAlerts\Services\ErrorAlertManager;

/**
 * @method static bool handle(\Throwable $exception, string $contextType = 'http', array $extra = [])
 * @see \Sadpcd\ErrorAlerts\Services\ErrorAlertManager
 */
class ErrorAlert extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ErrorAlertManager::class;
    }
}
