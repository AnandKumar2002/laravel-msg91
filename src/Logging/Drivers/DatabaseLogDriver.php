<?php

declare(strict_types=1);

namespace Parvion\Msg91\Logging\Drivers;

use Illuminate\Support\Facades\DB;
use Parvion\Msg91\Logging\Contracts\LogDriverInterface;

/**
 * Class DatabaseLogDriver
 *
 * Persists MSG91 activity to the msg91_logs database table.
 * Active when config('msg91.logging.driver') = 'database'.
 *
 * Requires the migration to have been published and run:
 *   php artisan vendor:publish --tag=msg91-migrations
 *   php artisan migrate
 *
 * If the table does not exist, the driver silently swallows
 * the QueryException to avoid crashing the application.
 *
 * @package Parvion\Msg91\Logging\Drivers
 */
class DatabaseLogDriver implements LogDriverInterface
{
    protected string $table = 'msg91_logs';

    // TODO: Phase 7 — logSuccess(): inserts a row with status='success'
    // TODO: Phase 7 — logFailure(): inserts a row with status='failed', error_message
    // TODO: Phase 7 — maskSensitive(array $payload): array
    //                 Redacts auth_key, otp digits from stored payloads

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
