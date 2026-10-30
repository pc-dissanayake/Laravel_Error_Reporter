<?php

namespace Sadpcd\ErrorAlerts\Filament\Resources\ErrorAlertResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Sadpcd\ErrorAlerts\Filament\Resources\ErrorAlertResource;

class ViewErrorAlert extends ViewRecord
{
    protected static string $resource = ErrorAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resolve')
                ->label('Mark as Resolved')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status !== 'resolved')
                ->action(function () {
                    $this->record->markAsResolved();
                    Notification::make()->title('Marked as resolved')->success()->send();
                }),
        ];
    }
}
