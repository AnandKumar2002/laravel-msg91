<?php

declare(strict_types=1);

namespace Parvion\Msg91\Logging\Drivers;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Parvion\Msg91\Logging\Contracts\LogDriverInterface;

/**
 * Class DatabaseLogDriver
 *
 * Persists MSG91 API activity to the `msg91_logs` database table.
 * Active when config('msg91.logging.driver') = 'database'.
 *
 * The table is created by the publishable migration:
 *   php artisan vendor:publish --tag=msg91-migrations
 *   php artisan migrate
 *
 * Graceful degradation:
 *   If the table doesn't exist or the database is unreachable, the driver
 *   catches the QueryException and falls back to a Log::warning() message.
 *   MSG91 API calls are NEVER interrupted by logging failures.
 */
class DatabaseLogDriver implements LogDriverInterface
{
    /**
     * Table name for MSG91 activity logs.
     */
    private const TABLE = 'msg91_logs';

    public function logSuccess(
        string $channel,
        string $action,
        string $recipient,
        array $request,
        array $response,
        int $httpStatus,
        int $durationMs,
    ): void {
        $this->insert([
            'channel' => $channel,
            'action' => $action,
            'recipient' => $this->truncateString($recipient, 255),
            'request_payload' => $this->safeJsonEncode($request),
            'response_payload' => $this->safeJsonEncode($response),
            'http_status' => $httpStatus,
            'status' => 'success',
            'error_message' => null,
            'duration_ms' => $durationMs,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
        $this->insert([
            'channel' => $channel,
            'action' => $action,
            'recipient' => $this->truncateString($recipient, 255),
            'request_payload' => $this->safeJsonEncode($request),
            'response_payload' => null,
            'http_status' => $httpStatus,
            'status' => 'failed',
            'error_message' => $this->truncateString($exception->getMessage(), 65535),
            'duration_ms' => $durationMs,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Insert a row into the msg91_logs table with graceful error handling.
     *
     * If the insert fails (table not migrated, DB connection down, etc.),
     * the error is silently logged to the application log channel.
     * The MSG91 API call is NEVER interrupted.
     */
    private function insert(array $row): void
    {
        try {
            DB::table(self::TABLE)->insert($row);
        } catch (QueryException $e) {
            // Graceful degradation: log the failure but don't interrupt the API call
            Log::warning('[MSG91] DatabaseLogDriver insert failed: '.$e->getMessage(), [
                'channel' => $row['channel'] ?? 'unknown',
                'action' => $row['action'] ?? 'unknown',
            ]);
        } catch (\Throwable $e) {
            // Catch any unexpected error — never let logging break API calls
            Log::warning('[MSG91] DatabaseLogDriver unexpected error: '.$e->getMessage());
        }
    }

    /**
     * Safely encode an array to JSON, returning null on failure.
     */
    private function safeJsonEncode(array $data): ?string
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json !== false ? $json : null;
    }

    /**
     * Truncate a string to fit database column constraints.
     */
    private function truncateString(string $value, int $maxLength): string
    {
        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return substr($value, 0, $maxLength - 3).'...';
    }
}
