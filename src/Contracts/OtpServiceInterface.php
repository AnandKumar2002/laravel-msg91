<?php

declare(strict_types=1);

namespace Parvion\Msg91\Contracts;

use Parvion\Msg91\DTOs\OtpData;
use Parvion\Msg91\Enums\OtpRetryType;
use Parvion\Msg91\Exceptions\FeatureDisabledException;
use Parvion\Msg91\Exceptions\InvalidOtpException;
use Parvion\Msg91\Exceptions\Msg91ApiException;
use Parvion\Msg91\Exceptions\Msg91RateLimitException;

/**
 * Interface OtpServiceInterface
 *
 * Defines the public contract for OTP operations.
 * Both the real Msg91 class (via ManagesOtp trait) and Msg91Fake must satisfy this contract.
 */
interface OtpServiceInterface
{
    /**
     * Send a new OTP to the given mobile number.
     *
     * @param  OtpData  $data  Strongly-typed OTP payload (mobile, template, length, expiry).
     * @return array Normalised MSG91 response array.
     *
     * @throws FeatureDisabledException If the OTP channel is disabled in config.
     * @throws Msg91RateLimitException If the local throttle or MSG91 rejects the request.
     * @throws Msg91ApiException On any other API-level error.
     */
    public function sendOtp(OtpData $data): array;

    /**
     * Verify an OTP entered by the user.
     *
     * @param  string  $mobile  Mobile number in E.164 format (e.g. '919876543210').
     * @param  string  $otp  The OTP entered by the user.
     * @return array Normalised MSG91 response array.
     *
     * @throws InvalidOtpException If the OTP is wrong, expired, or already used.
     * @throws Msg91ApiException On any other API-level error.
     */
    public function verifyOtp(string $mobile, string $otp): array;

    /**
     * Resend the OTP to the same mobile number (text channel).
     *
     * @param  string  $mobile  Mobile number in E.164 format.
     * @return array Normalised MSG91 response array.
     *
     * @throws FeatureDisabledException If the OTP channel is disabled in config.
     * @throws Msg91RateLimitException If resend is attempted during cooldown.
     * @throws Msg91ApiException On any other API-level error.
     */
    public function resendOtp(string $mobile): array;

    /**
     * Retry OTP delivery via an alternative channel (voice call or text).
     *
     * @param  string  $mobile  Mobile number in E.164 format.
     * @param  OtpRetryType  $type  Delivery channel: OtpRetryType::Voice or OtpRetryType::Text.
     * @return array Normalised MSG91 response array.
     *
     * @throws FeatureDisabledException If the OTP channel is disabled in config.
     * @throws Msg91ApiException On any other API-level error.
     */
    public function retryOtp(string $mobile, OtpRetryType $type): array;
}
