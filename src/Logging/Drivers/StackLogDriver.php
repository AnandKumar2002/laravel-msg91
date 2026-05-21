<?php

declare(strict_types=1);

namespace Parvion\Msg91\Logging\Drivers;

use Parvion\Msg91\Logging\Contracts\LogDriverInterface;

/**
 * Class StackLogDriver
 *
 * Delegates every log call to BOTH ChannelLogDriver and DatabaseLogDriver.
 * Active when config('msg91.logging.driver') = 'stack'.
 *
 * If either inner driver throws, the exception is caught and the other
 * driver still receives the call — logging never crashes the application.
 *
 * @package Parvion\Msg91\Logging\Drivers
 */
class StackLogDriver implements LogDriverInterface
{
    public function __construct(
        protected readonly ChannelLogDriver  $channelDriver,
        protected readonly DatabaseLogDriver $databaseDriver,
    ) {}

    public function logSuccess(
        string $channel,
        string $action,
        string $recipient,
        array  $request,
        array  $response,
        int    $httpStatus,
        int    $durationMs,
    ): void {
        // TODO: Phase 7 — call both drivers, wrap each in try/catch
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
        // TODO: Phase 7 — call both drivers, wrap each in try/catch
    }
}
