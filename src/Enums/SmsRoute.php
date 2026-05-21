<?php

declare(strict_types=1);

namespace Parvion\Msg91\Enums;

/**
 * Enum SmsRoute
 *
 * Represents the MSG91 SMS routing type.
 * The integer value maps directly to the MSG91 API route parameter.
 *
 * @package Parvion\Msg91\Enums
 */
enum SmsRoute: int
{
    // TODO: Phase 4 — Transactional = 4, Promotional = 1, International = 7
}
