<?php

declare(strict_types=1);

namespace Parvion\Msg91\Exceptions;

/**
 * Class InvalidOtpException
 *
 * Thrown when OTP verification fails — either the OTP is incorrect,
 * has expired, or has already been used.
 *
 * @package Parvion\Msg91\Exceptions
 */
class InvalidOtpException extends Msg91ApiException
{
    // TODO: Phase 3 — static expired(), static incorrect(), static alreadyUsed() named constructors
}
