<?php

namespace Sadpcd\ErrorAlerts\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Sadpcd\ErrorAlerts\Filament\Resources\ErrorAlertResource;
use Sadpcd\ErrorAlerts\Filament\Widgets\ErrorOverviewWidget;

class ErrorAlertsPlugin implements Plugin
{
    protected string $navigationGroup = 'Monitoring';
    protected ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected bool $enableWidget = true;

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'sadpcd-error-alerts';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            ErrorAlertResource::class,
        ]);

        if ($this->enableWidget) {
            $panel->widgets([
                ErrorOverviewWidget::class,
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
        // Bootstrapping logic for Filament panel if needed
    }

    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;
        return $this;
    }

    public function getNavigationGroup(): string
    {
        return $this->navigationGroup;
    }

    public function navigationIcon(?string $icon): static
    {
        $this->navigationIcon = $icon;
        return $this;
    }

    public function getNavigationIcon(): ?string
    {
        return $this->navigationIcon;
    }

    public function enableWidget(bool $enable = true): static
    {
        $this->enableWidget = $enable;
        return $this;
    }
}
