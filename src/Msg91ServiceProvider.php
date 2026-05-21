<?php

declare(strict_types=1);

namespace Parvion\Msg91;

use Illuminate\Support\ServiceProvider;

/**
 * Class Msg91ServiceProvider
 *
 * Registers all package bindings, publishes config and migrations,
 * and wires up event-listener pairs. Auto-discovered by Laravel via
 * the extra.laravel key in composer.json.
 *
 * @package Parvion\Msg91
 */
class Msg91ServiceProvider extends ServiceProvider
{
    /**
     * Register package services into the container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/msg91.php',
            'msg91'
        );

        // TODO: Phase 1 — bind Msg91ClientInterface, OtpServiceInterface, SmsServiceInterface
        // TODO: Phase 1 — singleton bind Msg91::class
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/Config/msg91.php' => config_path('msg91.php'),
            ], 'msg91-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'msg91-migrations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/msg91'),
            ], 'msg91-views');
        }

        // Load package Blade views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'msg91');

        // Register Livewire components (only if Livewire is installed and enabled in config)
        if (config('msg91.livewire.register_components', true)) {
            $this->bootLivewireComponents();
        }

        // TODO: Phase 7 — register event-listener pairs (OtpSent, MessageFailed → LogMsg91Activity)
        //                  based on config('msg91.logging.driver')
    }

    /**
     * Register built-in Livewire components if Livewire is available.
     */
    protected function bootLivewireComponents(): void
    {
        if (! class_exists(\Livewire\Livewire::class)) {
            return;
        }

        $componentName = config('msg91.livewire.otp_component_name', 'msg91-otp-verification');

        \Livewire\Livewire::component($componentName, \Parvion\Msg91\Livewire\OtpVerification::class);

        // TODO: Phase 5 — register additional Livewire components (email, whatsapp)
    }
}
