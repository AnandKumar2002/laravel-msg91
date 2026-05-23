<?php

declare(strict_types=1);

namespace Parvion\Msg91\Enums;

/**
 * Enum SmsRoute
 *
 * Represents the MSG91 SMS routing type.
 * The integer value maps directly to the MSG91 `route` API parameter.
 *
 * Usage:
 *   SmsData::fromArray(['route' => SmsRoute::Transactional, ...])
 */
enum SmsRoute: int
{
    /** Route 1 — Promotional SMS (marketing, offers). DND numbers are filtered. */
    case Promotional = 1;

    /** Route 4 — Transactional SMS (OTPs, alerts, account info). Highest priority. */
    case Transactional = 4;

    /** Route 7 — International SMS delivery. */
    case International = 7;

    /**
     * Return the human-readable label for this route.
     */
    public function label(): string
    {
        return match ($this) {
            self::Promotional => 'Promotional',
            self::Transactional => 'Transactional',
            self::International => 'International',
        };
    }
}
