<?php

declare(strict_types=1);

namespace Parvion\Msg91\Exceptions;

/**
 * Class FeatureDisabledException
 *
 * Thrown when a caller attempts to use a MSG91 channel (OTP, SMS, Email,
 * WhatsApp) that has been disabled via the config features flags.
 *
 * Example:
 *   // config/msg91.php
 *   'features' => ['whatsapp' => false]
 *
 *   Msg91::sendWhatsApp($data);
 *   // throws FeatureDisabledException: "The [whatsapp] feature is disabled."
 *
 * @package Parvion\Msg91\Exceptions
 */
class FeatureDisabledException extends Msg91ApiException
{
    // TODO: Phase 2 — static make(string $feature): self
    //                 Returns a pre-formatted exception for the given feature name.
}
