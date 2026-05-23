<?php

declare(strict_types=1);

namespace Parvion\Msg91\Logging\Contracts;

/**
 * Interface LogDriverInterface
 *
 * All MSG91 log drivers must implement this contract.
 * The driver is resolved by LogDriverManager based on config('msg91.logging.driver').
 *
 * Drivers:
 *   NullLogDriver     — discards everything (default, zero overhead)
 *   ChannelLogDriver  — writes to a Laravel log channel
 *   DatabaseLogDriver — persists to the msg91_logs table
 *   StackLogDriver    — delegates to both ChannelLogDriver + DatabaseLogDriver
 */
interface LogDriverInterface
{
    /**
     * Record a successful API call.
     *
     * @param  string  $channel  e.g. 'otp', 'sms', 'email', 'whatsapp'
     * @param  string  $action  e.g. 'send_otp', 'verify_otp', 'send_sms'
     * @param  string  $recipient  Mobile number, email address, etc.
     * @param  array  $request  Payload sent to MSG91
     * @param  array  $response  Raw response from MSG91
     * @param  int  $httpStatus  HTTP status code
     * @param  int  $durationMs  Round-trip duration in milliseconds
     */
    public function logSuccess(
        string $channel,
        string $action,
        string $recipient,
        array $request,
        array $response,
        int $httpStatus,
        int $durationMs,
    ): void;

    /**
     * Record a failed API call.
     *
     * @param  \Throwable  $exception  The exception that caused the failure
     * @param  int|null  $httpStatus  HTTP status code if available
     */
    public function logFailure(
        string $channel,
        string $action,
        string $recipient,
        array $request,
        \Throwable $exception,
        ?int $httpStatus,
        int $durationMs,
    ): void;
}
