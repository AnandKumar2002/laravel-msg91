<?php

declare(strict_types=1);

namespace Parvion\Msg91\Traits\Behaviors;

/**
 * Trait ManagesThrottling
 *
 * Enforces local rate limiting on outbound API requests using Laravel's
 * Cache driver. Prevents exceeding MSG91 API quotas without relying solely
 * on server-side 429 responses.
 *
 * Config keys: msg91.throttle.max_attempts, msg91.throttle.decay_seconds
 *
 * @package Parvion\Msg91\Traits\Behaviors
 */
trait ManagesThrottling
{
    // TODO: Phase 2 — checkThrottle(), incrementThrottle(), getRateLimitKey()
}
