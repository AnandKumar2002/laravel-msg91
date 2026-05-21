<?php

declare(strict_types=1);

namespace Parvion\Msg91;

use Illuminate\Support\ServiceProvider;
use Parvion\Msg91\Contracts\Msg91ClientInterface;
use Parvion\Msg91\Contracts\OtpServiceInterface;
use Parvion\Msg91\Contracts\SmsServiceInterface;
use Parvion\Msg91\Events\MessageFailed;
use Parvion\Msg91\Events\OtpSent;
use Parvion\Msg91\Http\Msg91Client;
use Parvion\Msg91\Listeners\LogMsg91Activity;
use Parvion\Msg91\Logging\LogDriverManager;

/**
 * Class Msg91ServiceProvider
 *
 * Registers all package bindings, publishes config/migrations,
 * and wires up event-listener pairs. Auto-discovered by Laravel via
 * the extra.laravel key in composer.json.
 *
 * Container bindings:
 *   Msg91ClientInterface  → Msg91Client        (transient)
 *   LogDriverManager      → LogDriverManager   (singleton)
 *   Msg91::class          → Msg91              (singleton)
 *   OtpServiceInterface   → Msg91 singleton    (alias)
 *   SmsServiceInterface   → Msg91 singleton    (alias)
 *
 * @package Parvion\Msg91
 */
class Msg91ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/msg91.php', 'msg91');

        $this->app->singleton(LogDriverManager::class);

        $this->app->bind(Msg91ClientInterface::class, function ($app): Msg91Client {
            return new Msg91Client($app->make(LogDriverManager::class));
        });

        $this->app->singleton(\Parvion\Msg91\Support\PhoneNumberFormatter::class, function () {
            return new \Parvion\Msg91\Support\PhoneNumberFormatter(
                config('msg91.default_country_code', '91'),
            );
        });

        $this->app->singleton(\Parvion\Msg91\Support\Msg91Logger::class, function ($app) {
            return new \Parvion\Msg91\Support\Msg91Logger(
                $app->make(LogDriverManager::class),
            );
        });

        $this->app->singleton(Msg91::class, function ($app): Msg91 {
            return new Msg91(
                $app->make(Msg91ClientInterface::class),
                $app->make(LogDriverManager::class),
            );
        });

        $this->app->bind(OtpServiceInterface::class, fn ($app) => $app->make(Msg91::class));
        $this->app->bind(SmsServiceInterface::class, fn ($app) => $app->make(Msg91::class));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/Config/msg91.php' => config_path('msg91.php'),
            ], 'msg91-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'msg91-migrations');
        }

        $this->bootEventListeners();
    }

    protected function bootEventListeners(): void
    {
        $driver = config('msg91.logging.driver', 'null');

        if ($driver === 'null') {
            return;
        }

        $this->app['events']->listen(OtpSent::class, LogMsg91Activity::class);
        $this->app['events']->listen(MessageFailed::class, LogMsg91Activity::class);
    }
}
