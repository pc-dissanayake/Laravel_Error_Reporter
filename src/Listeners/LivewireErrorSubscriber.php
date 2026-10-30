<?php

namespace Sadpcd\ErrorAlerts\Listeners;

use Sadpcd\ErrorAlerts\Services\ErrorAlertManager;
use Throwable;

class LivewireErrorSubscriber
{
    protected ErrorAlertManager $manager;

    public function __construct(ErrorAlertManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Register Livewire exception listener if Livewire is present.
     */
    public function register(): void
    {
        if (!class_exists(\Livewire\Livewire::class)) {
            return;
        }

        try {
            \Livewire\Livewire::listen('failed', function ($component, $action, $params, $exception) {
                if ($exception instanceof Throwable) {
                    $this->manager->handle($exception, 'livewire', [
                        'component' => is_object($component) ? get_class($component) : (string) $component,
                        'action'    => (string) $action,
                        'params'    => is_array($params) ? $params : [],
                    ]);
                }
            });
        } catch (Throwable) {
            // Livewire version may vary; fail gracefully
        }
    }
}
