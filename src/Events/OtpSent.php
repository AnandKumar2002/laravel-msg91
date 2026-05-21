<?php

declare(strict_types=1);

namespace Parvion\Msg91\Events;

use Parvion\Msg91\DTOs\OtpData;

/**
 * Class OtpSent
 *
 * Fired after a successful OTP send request to MSG91.
 * Carries the original OtpData DTO and the raw API response.
 *
 * @package Parvion\Msg91\Events
 */
class OtpSent
{
    // TODO: Phase 3 — constructor(readonly OtpData $otpData, readonly array $response)
}
