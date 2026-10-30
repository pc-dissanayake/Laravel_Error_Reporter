<?php

namespace Sadpcd\ErrorAlerts\Filament;

use Sadpcd\ErrorReporter\Filament\ErrorReporterPlugin;

class ErrorAlertsPlugin extends ErrorReporterPlugin
{
    public function getId(): string
    {
        return 'sadpcd-error-alerts';
    }
}
