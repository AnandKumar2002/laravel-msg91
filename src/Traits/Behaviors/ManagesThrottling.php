<?php

declare(strict_types=1);

namespace Parvion\Msg91\Traits\Behaviors;

use Illuminate\Support\Facades\Cache;
use Parvion\Msg91\Exceptions\Msg91RateLimitException;

/**
 * Trait ManagesThrottling
 *
 * Enforces a local rate limit on outbound MSG91 API requests using Laravel's
 * Cache driver. Prevents exceeding MSG91 API quotas without relying solely on
 * server-side 429 responses.
 *
 * Each unique $identifier (typically a mobile number or feature key) gets its
 * own sliding window counter. When the window expires, the counter resets.
 *
 * Config keys consumed (via InteractsWithConfig):
 *   msg91.throttle.max_attempts  (default: 60 requests)
 *   msg91.throttle.decay_seconds (default: 60 seconds)
 *
 * Usage in service traits:
 *   $this->checkThrottle('otp:' . $mobile);   // throws if over limit
 *
 * @uses InteractsWithConfig
 */
trait ManagesThrottling
{
    /**
     * Check whether the given identifier has exceeded the rate limit.
     * Increments the attempt counter on every call.
     *
     * @param  string  $identifier  Unique key for this throttle slot (e.g. 'otp:919876543210').
     *
     * @throws Msg91RateLimitException If the rate limit has been exceeded.
     */
    protected function checkThrottle(string $identifier): void
    {
        $maxAttempts = $this->getThrottleMaxAttempts();
        $decay = $this->getThrottleDecaySeconds();
        $cacheKey = $this->getRateLimitKey($identifier);

        $attempts = (int) Cache::get($cacheKey, 0);

        if ($attempts >= $maxAttempts) {
            $ttl = (int) Cache::getStore()->connection()->ttl($cacheKey);

            // Fallback: use the full decay window if TTL is unavailable
            $retryAfter = $ttl > 0 ? $ttl : $decay;

            throw Msg91RateLimitException::localThrottle($identifier, $retryAfter);
        }

        // Increment counter; set expiry only on first hit so the window is sliding
        if ($attempts === 0) {
            Cache::put($cacheKey, 1, $decay);
        } else {
            Cache::increment($cacheKey);
        }
    }

    /**
     * Manually reset the throttle counter for a given identifier.
     * Useful in tests or when a user successfully verifies their OTP.
     */
    protected function resetThrottle(string $identifier): void
    {
        Cache::forget($this->getRateLimitKey($identifier));
    }

    /**
     * Return the number of remaining attempts for a given identifier.
     */
    protected function remainingAttempts(string $identifier): int
    {
        $maxAttempts = $this->getThrottleMaxAttempts();
        $used = (int) Cache::get($this->getRateLimitKey($identifier), 0);

        return max(0, $maxAttempts - $used);
    }

    /**
     * Build the Cache key for a given throttle identifier.
     */
    protected function getRateLimitKey(string $identifier): string
    {
        return 'msg91:throttle:'.hash('xxh3', $identifier);
    }
}
