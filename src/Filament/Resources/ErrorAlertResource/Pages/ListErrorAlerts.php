<?php

namespace Sadpcd\ErrorAlerts\Filament\Resources\ErrorAlertResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Sadpcd\ErrorAlerts\Filament\Resources\ErrorAlertResource;

class ListErrorAlerts extends ListRecords
{
    protected static string $resource = ErrorAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
