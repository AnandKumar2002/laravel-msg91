<?php

declare(strict_types=1);

namespace Parvion\Msg91\Support;

use Parvion\Msg91\Logging\LogDriverManager;

/**
 * Class Msg91Logger
 *
 * A thin, injectable façade over LogDriverManager that provides a clean
 * developer API for logging MSG91 API activity from service traits.
 *
 * Resolved from the container as a singleton. Service traits call it via
 * $this->logManager (the LogDriverManager injected into Msg91), but
 * standalone classes (like Msg91Client) can use Msg91Logger directly.
 *
 * Sensitive field masking:
 *   The following keys are automatically redacted from request payloads
 *   before any log write to prevent secret leakage:
 *   'authkey', 'otp', 'auth_key'
 *
 * @package Parvion\Msg91\Support
 */
class Msg91Logger
{
    /** Keys to redact from request/response payloads before logging. */
    private const SENSITIVE_KEYS = ['authkey', 'auth_key', 'otp', 'password', 'token'];

    public function __construct(
        private readonly LogDriverManager $manager,
    ) {}

    /**
     * Log a successful API call.
     *
     * @param  string  $channel     MSG91 channel: 'otp', 'sms', 'email', 'whatsapp'.
     * @param  string  $action      Action: 'send_otp', 'verify_otp', 'send_sms', etc.
     * @param  string  $recipient   Mobile number or email address.
     * @param  array   $request     Request payload sent to MSG91.
     * @param  array   $response    Normalised response from MSG91.
     * @param  int     $httpStatus  HTTP status code.
     * @param  int     $durationMs  Round-trip duration in milliseconds.
     */
    public function success(
        string $channel,
        string $action,
        string $recipient,
        array  $request,
        array  $response,
        int    $httpStatus,
        int    $durationMs,
    ): void {
        $this->manager->logSuccess(
            $channel,
            $action,
            $recipient,
            $this->mask($request),
            $response,
            $httpStatus,
            $durationMs,
        );
    }

    /**
     * Log a failed API call.
     *
     * @param  string      $channel
     * @param  string      $action
     * @param  string      $recipient
     * @param  array       $request
     * @param  \Throwable  $exception
     * @param  int|null    $httpStatus
     * @param  int         $durationMs
     */
    public function failure(
        string     $channel,
        string     $action,
        string     $recipient,
        array      $request,
        \Throwable $exception,
        ?int       $httpStatus,
        int        $durationMs,
    ): void {
        $this->manager->logFailure(
            $channel,
            $action,
            $recipient,
            $this->mask($request),
            $exception,
            $httpStatus,
            $durationMs,
        );
    }

    /**
     * Redact sensitive fields from a payload array before it is logged.
     * Operates recursively on nested arrays.
     *
     * @param  array  $payload
     * @return array
     */
    public function mask(array $payload): array
    {
        $masked = [];

        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                $masked[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $masked[$key] = $this->mask($value);
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }
}
