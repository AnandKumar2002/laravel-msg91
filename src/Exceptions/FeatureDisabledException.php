<?php

declare(strict_types=1);

namespace Parvion\Msg91\Exceptions;

/**
 * Class FeatureDisabledException
 *
 * Thrown when a caller attempts to use a MSG91 channel (otp, sms, email,
 * whatsapp) that has been disabled via the config feature flags.
 *
 * Example:
 *   # config/msg91.php
 *   'features' => ['whatsapp' => false]
 *
 *   Msg91::sendWhatsApp($data);
 *   // throws: FeatureDisabledException — The [whatsapp] MSG91 feature is disabled.
 */
class FeatureDisabledException extends Msg91ApiException
{
    /**
     * Create a pre-formatted exception for the given feature name.
     *
     * @param  string  $feature  One of: 'otp', 'sms', 'email', 'whatsapp'.
     */
    public static function make(string $feature): static
    {
        return new static(
            "The [{$feature}] MSG91 feature is disabled. ".
            'Enable it via MSG91_FEATURE_'.strtoupper($feature).'=true in your .env file.',
            403,
        );
    }
}
