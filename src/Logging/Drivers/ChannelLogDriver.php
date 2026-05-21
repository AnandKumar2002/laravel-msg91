<?php

declare(strict_types=1);

namespace Parvion\Msg91\Logging\Drivers;

use Illuminate\Support\Facades\Log;
use Parvion\Msg91\Logging\Contracts\LogDriverInterface;

/**
 * Class ChannelLogDriver
 *
 * Writes MSG91 activity to a Laravel log channel.
 * Active when config('msg91.logging.driver') = 'log'.
 *
 * Uses config('msg91.logging.channel') to select the channel
 * and config('msg91.logging.level') for the log level.
 *
 * @package Parvion\Msg91\Logging\Drivers
 */
class ChannelLogDriver implements LogDriverInterface
{
    // TODO: Phase 7 — constructor injects channel name and level from config
    // TODO: Phase 7 — logSuccess(): structured context array → Log::channel()->info()
    // TODO: Phase 7 — logFailure(): structured context array → Log::channel()->error()
    //                 Masks sensitive fields (auth_key, otp value) before writing

    public function logSuccess(
        string $channel,
        string $action,
        string $recipient,
        array  $request,
        array  $response,
        int    $httpStatus,
        int    $durationMs,
    ): void {
        // TODO: Phase 7
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
        // TODO: Phase 7
    }
}
