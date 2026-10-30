<?php

namespace Sadpcd\ErrorAlerts\Filament\Resources;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;
use Sadpcd\ErrorAlerts\Filament\Resources\ErrorAlertResource\Pages\ListErrorAlerts;
use Sadpcd\ErrorAlerts\Filament\Resources\ErrorAlertResource\Pages\ViewErrorAlert;
use Sadpcd\ErrorAlerts\Models\ErrorAlert;

class ErrorAlertResource extends Resource
{
    protected static ?string $model = ErrorAlert::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Monitoring';

    protected static ?string $navigationLabel = '500 Error Reports';

    protected static ?int $navigationSort = 100;

    public static function getNavigationBadge(): ?string
    {
        try {
            $count = ErrorAlert::where('status', 'unresolved')->count();
            return $count > 0 ? (string) $count : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Exception Overview')
                ->columns(2)
                ->schema([
                    TextInput::make('exception_class')
                        ->label('Exception Class')
                        ->disabled(),
                    TextInput::make('status')
                        ->label('Status')
                        ->disabled(),
                    TextInput::make('file')
                        ->label('File')
                        ->columnSpan(1)
                        ->disabled(),
                    TextInput::make('line')
                        ->label('Line')
                        ->columnSpan(1)
                        ->disabled(),
                    Textarea::make('message')
                        ->label('Exception Message')
                        ->rows(3)
                        ->columnSpanFull()
                        ->disabled(),
                ]),

            Section::make('Request Context')
                ->columns(3)
                ->schema([
                    TextInput::make('context_type')
                        ->label('Context')
                        ->disabled(),
                    TextInput::make('method')
                        ->label('HTTP Method')
                        ->disabled(),
                    TextInput::make('ip_address')
                        ->label('Client IP')
                        ->disabled(),
                    TextInput::make('url')
                        ->label('Requested URL')
                        ->columnSpan(2)
                        ->disabled(),
                    TextInput::make('user_email')
                        ->label('Auth User')
                        ->disabled(),
                    Textarea::make('user_agent')
                        ->label('User Agent')
                        ->rows(2)
                        ->columnSpanFull()
                        ->disabled(),
                ]),

            Section::make('Stack Trace')
                ->collapsible()
                ->schema([
                    Textarea::make('stack_trace')
                        ->label('Full Stack Trace')
                        ->rows(15)
                        ->columnSpanFull()
                        ->extraAttributes(['class' => 'font-mono text-xs'])
                        ->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'danger'  => 'unresolved',
                        'success' => 'resolved',
                        'gray'    => 'ignored',
                    ])
                    ->sortable(),

                TextColumn::make('context_type')
                    ->label('Type')
                    ->badge()
                    ->colors([
                        'info'    => 'http',
                        'purple'  => 'inertia',
                        'pink'    => 'livewire',
                        'warning' => 'queue',
                        'gray'    => 'console',
                    ]),

                TextColumn::make('exception_class')
                    ->label('Exception')
                    ->formatStateUsing(fn (string $state) => class_basename($state))
                    ->description(fn (ErrorAlert $record) => $record->file . ':' . $record->line)
                    ->searchable()
                    ->copyable(),

                TextColumn::make('message')
                    ->label('Message')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(fn (ErrorAlert $record) => $record->message),

                TextColumn::make('url')
                    ->label('URL')
                    ->limit(35)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('occurrences_count')
                    ->label('Count')
                    ->badge()
                    ->color('danger')
                    ->sortable(),

                TextColumn::make('last_seen_at')
                    ->label('Last Seen')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('last_seen_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'unresolved' => 'Unresolved',
                        'resolved'   => 'Resolved',
                        'ignored'    => 'Ignored',
                    ]),

                SelectFilter::make('context_type')
                    ->options([
                        'http'     => 'HTTP Web',
                        'inertia'  => 'Inertia / Vite SPA',
                        'livewire' => 'Livewire Component',
                        'queue'    => 'Queued Job',
                        'console'  => 'Artisan / CLI',
                    ]),
            ])
            ->actions([
                ViewAction::make(),

                Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ErrorAlert $record) => $record->status !== 'resolved')
                    ->action(function (ErrorAlert $record) {
                        $record->markAsResolved();
                        Notification::make()->title('Marked as resolved')->success()->send();
                    }),

                Action::make('ignore')
                    ->label('Ignore')
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->visible(fn (ErrorAlert $record) => $record->status !== 'ignored')
                    ->action(function (ErrorAlert $record) {
                        $record->markAsIgnored();
                        Notification::make()->title('Error ignored')->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('mark_resolved')
                    ->label('Mark Resolved')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Collection $records) {
                        $records->each->markAsResolved();
                        Notification::make()->title('Selected errors marked as resolved')->success()->send();
                    }),

                DeleteBulkAction::make(),
            ])
            ->headerActions([
                Action::make('test_alert')
                    ->label('Send Test Alert')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function () {
                        Artisan::call('error-reporter:test');
                        Notification::make()
                            ->title('Simulated 500 error sent!')
                            ->body('Check your recipient inbox.')
                            ->success()
                            ->send();
                    }),

                Action::make('clear_throttles')
                    ->label('Clear Cooldowns')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function () {
                        Artisan::call('error-reporter:clear');
                        Notification::make()
                            ->title('Cooldowns cleared')
                            ->body('New identical errors will dispatch immediately.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListErrorAlerts::route('/'),
            'view'  => ViewErrorAlert::route('/{record}'),
        ];
    }
}
