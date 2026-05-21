<?php

declare(strict_types=1);

namespace Parvion\Msg91\Events;

use Carbon\CarbonImmutable;
use Parvion\Msg91\DTOs\OtpData;

/**
 * Class OtpSent
 *
 * Dispatched after a successful call to Msg91::sendOtp().
 * Listeners can use this event to:
 *   - Record OTP send attempts in the database
 *   - Track analytics / conversion rates
 *   - Trigger follow-up notifications
 *
 * Usage:
 *   Event::listen(OtpSent::class, function (OtpSent $event) {
 *       Log::info('OTP sent to ' . $event->otpData->mobile);
 *   });
 *
 * @package Parvion\Msg91\Events
 */
class OtpSent
{
    /**
     * UTC timestamp of when the OTP was successfully dispatched.
     */
    public readonly CarbonImmutable $sentAt;

    public function __construct(
        /**
         * The strongly-typed OTP payload that was sent.
         * Contains mobile, templateId, otpLength, otpExpiry, variables.
         */
        public readonly OtpData $otpData,

        /**
         * The normalised response array returned by MSG91.
         * Shape: ['type' => 'success', 'message' => '...', 'data' => [...]]
         */
        public readonly array $response,

        /**
         * The formatted mobile number (E.164 without +) that was actually dialled.
         * May differ from $otpData->mobile if country code was auto-prepended.
         */
        public readonly string $formattedMobile,
    ) {
        $this->sentAt = CarbonImmutable::now('UTC');
    }

    /**
     * Return the mobile number the OTP was sent to.
     * Convenience alias for $this->formattedMobile.
     */
    public function mobile(): string
    {
        return $this->formattedMobile;
    }

    /**
     * Return the MSG91 template ID that was used.
     */
    public function templateId(): string
    {
        return $this->otpData->templateId;
    }
}
