<?php

declare(strict_types=1);

namespace Parvion\Msg91\Logging\Drivers;

use Illuminate\Support\Facades\Log;
use Parvion\Msg91\Logging\Contracts\LogDriverInterface;

/**
 * Class StackLogDriver
 *
 * Delegates log calls to multiple drivers simultaneously (fan-out).
 * Active when config('msg91.logging.driver') = 'stack'.
 *
 * Default stack: ChannelLogDriver + DatabaseLogDriver.
 *
 * Fault isolation:
 *   Each driver is called inside its own try/catch. If ChannelLogDriver
 *   fails, DatabaseLogDriver still executes (and vice versa). This
 *   guarantees that a broken log channel never causes data loss in the
 *   database log, and a missing migration never silences channel logs.
 */
class StackLogDriver implements LogDriverInterface
{
    /**
     * @var LogDriverInterface[]
     */
    private array $drivers;

    /**
     * @param  LogDriverInterface  ...$drivers  Drivers to fan-out to.
     */
    public function __construct(LogDriverInterface ...$drivers)
    {
        $this->drivers = $drivers;
    }

    public function logSuccess(
        string $channel,
        string $action,
        string $recipient,
        array $request,
        array $response,
        int $httpStatus,
        int $durationMs,
    ): void {
        foreach ($this->drivers as $driver) {
            try {
                $driver->logSuccess(
                    $channel, $action, $recipient,
                    $request, $response, $httpStatus, $durationMs
                );
            } catch (\Throwable $e) {
                // Fault isolation: log the error and continue to the next driver
                $this->reportDriverFailure($driver, 'logSuccess', $e);
            }
        }
    }

    public function logFailure(
        string $channel,
        string $action,
        string $recipient,
        array $request,
        \Throwable $exception,
        ?int $httpStatus,
        int $durationMs,
    ): void {
        foreach ($this->drivers as $driver) {
            try {
                $driver->logFailure(
                    $channel, $action, $recipient,
                    $request, $exception, $httpStatus, $durationMs
                );
            } catch (\Throwable $e) {
                // Fault isolation: log the error and continue to the next driver
                $this->reportDriverFailure($driver, 'logFailure', $e);
            }
        }
    }

    /**
     * Report a driver-level failure without interrupting the fan-out.
     */
    private function reportDriverFailure(
        LogDriverInterface $driver,
        string $method,
        \Throwable $e,
    ): void {
        $driverClass = get_class($driver);

        // Use a direct Log call — this should not go through our drivers
        // to avoid infinite recursion if the log channel itself is broken.
        try {
            Log::warning(
                "[MSG91] StackLogDriver: {$driverClass}::{$method}() failed — {$e->getMessage()}"
            );
        } catch (\Throwable) {
            // Last resort: if even the fallback log fails, silently swallow.
            // MSG91 API calls must never be interrupted by logging.
        }
    }
}
