<?php

declare(strict_types=1);

namespace Parvion\Msg91\Logging;

use InvalidArgumentException;
use Parvion\Msg91\Logging\Contracts\LogDriverInterface;
use Parvion\Msg91\Logging\Drivers\ChannelLogDriver;
use Parvion\Msg91\Logging\Drivers\DatabaseLogDriver;
use Parvion\Msg91\Logging\Drivers\NullLogDriver;
use Parvion\Msg91\Logging\Drivers\StackLogDriver;

/**
 * Class LogDriverManager
 *
 * Factory / manager that resolves the correct LogDriverInterface implementation
 * based on config('msg91.logging.driver').
 *
 * Resolution map:
 *   'null'     → NullLogDriver      (default — zero overhead)
 *   'log'      → ChannelLogDriver   (Laravel log channel)
 *   'database' → DatabaseLogDriver  (msg91_logs table)
 *   'stack'    → StackLogDriver     (channel + database)
 *
 * Bound in the container as a singleton by Msg91ServiceProvider.
 * Injected into Msg91Client so every API call is automatically logged.
 *
 * Usage (manual):
 *   $manager = app(LogDriverManager::class);
 *   $manager->driver()->logSuccess(...);
 */
class LogDriverManager
{
    /**
     * Cached resolved driver instance.
     */
    protected ?LogDriverInterface $resolved = null;

    /**
     * Resolve and return the configured log driver.
     * Result is cached after the first call.
     */
    public function driver(): LogDriverInterface
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $driverName = config('msg91.logging.driver', 'null');

        $this->resolved = match ($driverName) {
            'null' => new NullLogDriver,
            'log' => new ChannelLogDriver,
            'database' => new DatabaseLogDriver,
            'stack' => new StackLogDriver(new ChannelLogDriver, new DatabaseLogDriver),
            default => throw new InvalidArgumentException(
                "MSG91 logging driver [{$driverName}] is not supported. ".
                'Supported drivers: null, log, database, stack.'
            ),
        };

        return $this->resolved;
    }

    /**
     * Proxy logSuccess to the resolved driver.
     */
    public function logSuccess(
        string $channel,
        string $action,
        string $recipient,
        array $request,
        array $response,
        int $httpStatus,
        int $durationMs,
    ): void {
        $this->driver()->logSuccess(
            $channel, $action, $recipient,
            $request, $response, $httpStatus, $durationMs
        );
    }

    /**
     * Proxy logFailure to the resolved driver.
     */
    public function logFailure(
        string $channel,
        string $action,
        string $recipient,
        array $request,
        \Throwable $exception,
        ?int $httpStatus,
        int $durationMs,
    ): void {
        $this->driver()->logFailure(
            $channel, $action, $recipient,
            $request, $exception, $httpStatus, $durationMs
        );
    }

    /**
     * Swap the driver at runtime (useful in tests).
     */
    public function swap(LogDriverInterface $driver): void
    {
        $this->resolved = $driver;
    }
}
