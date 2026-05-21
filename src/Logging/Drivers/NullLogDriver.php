<?php

declare(strict_types=1);

namespace Parvion\Msg91\Logging\Drivers;

use Parvion\Msg91\Logging\Contracts\LogDriverInterface;

/**
 * Class NullLogDriver
 *
 * The default logging driver — discards all log calls with zero overhead.
 * Active when config('msg91.logging.driver') = 'null'.
 *
 * No database writes, no log channel writes, no performance impact.
 *
 * @package Parvion\Msg91\Logging\Drivers
 */
class NullLogDriver implements LogDriverInterface
{
    public function logSuccess(
        string $channel,
        string $action,
        string $recipient,
        array  $request,
        array  $response,
        int    $httpStatus,
        int    $durationMs,
    ): void {
        // Intentionally empty — /dev/null for MSG91 logs
    }

    public function logFailure(
        string     $channel,
        string     $action,
        string     $recipient,
        array      $request,
        \Throwable $exception,
        ?int       $httpStatus,
        int        $durationMs,
    ): void {
        // Intentionally empty — /dev/null for MSG91 logs
    }
}
