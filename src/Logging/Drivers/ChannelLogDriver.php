<?php

declare(strict_types=1);

namespace Parvion\Msg91\Logging\Drivers;

use Illuminate\Support\Facades\Log;
use Parvion\Msg91\Logging\Contracts\LogDriverInterface;

/**
 * Class ChannelLogDriver
 *
 * Writes MSG91 API activity to a Laravel log channel with structured context.
 * Active when config('msg91.logging.driver') = 'log'.
 *
 * Config keys consumed:
 *   msg91.logging.channel → Laravel log channel name (null = default)
 *   msg91.logging.level   → Log level for success calls ('info' by default)
 *
 * Success calls are logged at the configured level.
 * Failure calls are always logged at 'error' level regardless of config.
 *
 * Structured context includes: channel, action, recipient, http_status,
 * duration_ms, and a truncated request/response payload.
 *
 * @package Parvion\Msg91\Logging\Drivers
 */
class ChannelLogDriver implements LogDriverInterface
{
    /**
     * Maximum JSON payload size (in characters) logged for request/response.
     * Prevents enormous template payloads from bloating log files.
     */
    private const MAX_PAYLOAD_LENGTH = 2000;

    public function logSuccess(
        string $channel,
        string $action,
        string $recipient,
        array  $request,
        array  $response,
        int    $httpStatus,
        int    $durationMs,
    ): void {
        $level   = config('msg91.logging.level', 'info');
        $message = "[MSG91] {$channel}:{$action} → {$httpStatus} ({$durationMs}ms)";

        $this->logger()->log($level, $message, [
            'channel'    => $channel,
            'action'     => $action,
            'recipient'  => $recipient,
            'status'     => 'success',
            'http_code'  => $httpStatus,
            'duration'   => $durationMs,
            'request'    => $this->truncate($request),
            'response'   => $this->truncate($response),
        ]);
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
        $message = "[MSG91] {$channel}:{$action} FAILED → "
            . ($httpStatus ?? 'N/A')
            . " ({$durationMs}ms) — {$exception->getMessage()}";

        $this->logger()->error($message, [
            'channel'       => $channel,
            'action'        => $action,
            'recipient'     => $recipient,
            'status'        => 'failed',
            'http_code'     => $httpStatus,
            'duration'      => $durationMs,
            'request'       => $this->truncate($request),
            'error_class'   => get_class($exception),
            'error_message' => $exception->getMessage(),
            'error_trace'   => substr($exception->getTraceAsString(), 0, 1000),
        ]);
    }

    /**
     * Get the configured log channel instance.
     * Returns the default channel when msg91.logging.channel is null.
     *
     * @return \Psr\Log\LoggerInterface
     */
    private function logger(): \Psr\Log\LoggerInterface
    {
        $channelName = config('msg91.logging.channel');

        if ($channelName !== null) {
            return Log::channel($channelName);
        }

        return Log::getFacadeRoot();
    }

    /**
     * Truncate an array payload to prevent oversized log entries.
     * Converts to JSON, truncates, then marks as truncated.
     */
    private function truncate(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return '{"_error":"json_encode failed"}';
        }

        if (strlen($json) > self::MAX_PAYLOAD_LENGTH) {
            return substr($json, 0, self::MAX_PAYLOAD_LENGTH) . '…[TRUNCATED]';
        }

        return $json;
    }
}
