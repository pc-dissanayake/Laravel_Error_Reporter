<?php

namespace Sadpcd\ErrorAlerts\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Sadpcd\ErrorAlerts\Models\ErrorAlert;
use Throwable;

class ErrorOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        try {
            $total24h = ErrorAlert::where('last_seen_at', '>=', now()->subHours(24))->sum('occurrences_count');
            $unresolved = ErrorAlert::where('status', 'unresolved')->count();
            $topError = ErrorAlert::orderByDesc('occurrences_count')->first();

            $topErrorSummary = $topError
                ? class_basename($topError->exception_class) . " ({$topError->occurrences_count}x)"
                : 'None';

            return [
                Stat::make('500 Errors (24h)', number_format($total24h))
                    ->description('Occurrences across system')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color($total24h > 0 ? 'danger' : 'success'),

                Stat::make('Unresolved Errors', number_format($unresolved))
                    ->description('Unique issues needing review')
                    ->descriptionIcon('heroicon-m-shield-exclamation')
                    ->color($unresolved > 0 ? 'warning' : 'success'),

                Stat::make('Most Frequent Error', $topErrorSummary)
                    ->description($topError ? $topError->file . ':' . $topError->line : 'All clean')
                    ->descriptionIcon('heroicon-m-bolt')
                    ->color($topError ? 'danger' : 'gray'),
            ];
        } catch (Throwable) {
            return [
                Stat::make('Error Alerts', 'DB Inactive')
                    ->description('Error log table not yet migrated')
                    ->color('gray'),
            ];
        }
    }
}
