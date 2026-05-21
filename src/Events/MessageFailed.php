<?php

declare(strict_types=1);

namespace Parvion\Msg91\Events;

use Carbon\CarbonImmutable;

/**
 * Class MessageFailed
 *
 * Dispatched whenever a MSG91 API call fails after all retry attempts
 * are exhausted — for any channel (OTP, SMS, Email, WhatsApp).
 *
 * This event is also dispatched by SendSmsJob::failed() when the queued
 * job exceeds its maximum retry limit.
 *
 * Listeners can use this event to:
 *   - Alert on-call engineers via Slack / PagerDuty
 *   - Record failures in the database for auditing
 *   - Re-queue the message via a fallback provider
 *   - Increment failure counters in monitoring dashboards
 *
 * Usage:
 *   Event::listen(MessageFailed::class, function (MessageFailed $event) {
 *       Log::error("MSG91 {$event->channel} failed: " . $event->exception->getMessage());
 *   });
 *
 * @package Parvion\Msg91\Events
 */
class MessageFailed
{
    /**
     * UTC timestamp of when the failure was recorded.
     */
    public readonly CarbonImmutable $failedAt;

    public function __construct(
        /**
         * The MSG91 channel that failed: 'otp', 'sms', 'email', 'whatsapp'.
         */
        public readonly string     $channel,

        /**
         * The request payload that was attempted (sensitive fields already masked).
         */
        public readonly array      $payload,

        /**
         * The exception that caused the failure.
         * May be Msg91ApiException, Msg91RateLimitException, or a connection error.
         */
        public readonly \Throwable $exception,

        /**
         * The intended recipient (mobile number or email address).
         */
        public readonly string     $recipient = '',
    ) {
        $this->failedAt = CarbonImmutable::now('UTC');
    }

    /**
     * Return a short human-readable summary of this failure.
     */
    public function summary(): string
    {
        return sprintf(
            'MSG91 [%s] failed for [%s]: %s',
            $this->channel,
            $this->recipient ?: 'unknown',
            $this->exception->getMessage(),
        );
    }

    /**
     * Check if the failure was caused by a rate limit.
     */
    public function isRateLimited(): bool
    {
        return $this->exception instanceof \Parvion\Msg91\Exceptions\Msg91RateLimitException;
    }

    /**
     * Check if the failure was a feature-disabled error.
     */
    public function isFeatureDisabled(): bool
    {
        return $this->exception instanceof \Parvion\Msg91\Exceptions\FeatureDisabledException;
    }
}
