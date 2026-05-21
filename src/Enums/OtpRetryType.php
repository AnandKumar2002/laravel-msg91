<?php

declare(strict_types=1);

namespace Parvion\Msg91\Enums;

/**
 * Enum OtpRetryType
 *
 * Represents the channel through which an OTP retry will be delivered.
 * The string value maps directly to the MSG91 `retrytype` API parameter.
 *
 * Usage:
 *   Msg91::retryOtp('919876543210', OtpRetryType::Voice);
 *
 * @package Parvion\Msg91\Enums
 */
enum OtpRetryType: string
{
    /** Deliver the OTP via an automated voice call. */
    case Voice = 'voice';

    /** Deliver the OTP via a text SMS. */
    case Text = 'text';
}
