<?php

declare(strict_types=1);

namespace Parvion\Msg91\Traits\Behaviors;

use Parvion\Msg91\Exceptions\FeatureDisabledException;
use Parvion\Msg91\Exceptions\InvalidOtpException;
use Parvion\Msg91\Exceptions\Msg91ApiException;
use Parvion\Msg91\Exceptions\Msg91RateLimitException;

/**
 * Trait WithRetries
 *
 * Wraps API call callables with configurable exponential back-off retry logic.
 *
 * Retry policy:
 *   - Only retries on transient errors (connection failures, 5xx responses).
 *   - Never retries on 4xx client errors (wrong OTP, disabled feature, etc.).
 *   - Never retries on Msg91RateLimitException (would make throttling worse).
 *   - Sleep doubles on each attempt: sleep_ms → 2×sleep_ms → 4×sleep_ms …
 *
 * Config keys consumed (via InteractsWithConfig):
 *   msg91.retry.attempts           (default: 3)
 *   msg91.retry.sleep_milliseconds (default: 200ms)
 *
 * @uses InteractsWithConfig
 *
 * @package Parvion\Msg91\Traits\Behaviors
 */
trait WithRetries
{
    /**
     * Execute $callback with automatic retry on transient failures.
     *
     * @template T
     * @param  callable(): T  $callback
     * @return T
     *
     * @throws Msg91ApiException  Re-throws the last exception after all attempts are exhausted.
     */
    protected function withRetry(callable $callback): mixed
    {
        $attempts  = $this->getRetryAttempts();
        $sleepBase = $this->getRetrySleepMs();

        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return $callback();
            } catch (\Throwable $e) {
                $lastException = $e;

                // Non-retryable error — rethrow immediately
                if (! $this->isRetryable($e)) {
                    throw $e;
                }

                // Last attempt — rethrow without sleeping
                if ($attempt === $attempts) {
                    throw $e;
                }

                // Exponential back-off: 200ms → 400ms → 800ms …
                $sleepMicros = ($sleepBase * (2 ** ($attempt - 1))) * 1000;
                usleep((int) $sleepMicros);
            }
        }

        // Unreachable, but satisfies static analysis
        throw $lastException ?? new Msg91ApiException('Retry loop exhausted without result.');
    }

    /**
     * Determine whether the given exception warrants a retry.
     *
     * Returns false for:
     *   - Rate limit exceptions (retrying immediately makes it worse)
     *   - Invalid OTP (wrong code — no point retrying)
     *   - Feature disabled (config issue — won't fix itself)
     *   - Any 4xx client error (developer/input error)
     *
     * Returns true for:
     *   - Connection timeouts / network errors
     *   - 5xx server errors from MSG91
     *   - Any non-HTTP exception (e.g. JSON decode failure)
     */
    protected function isRetryable(\Throwable $e): bool
    {
        if ($e instanceof Msg91RateLimitException) {
            return false;
        }

        if ($e instanceof InvalidOtpException) {
            return false;
        }

        if ($e instanceof FeatureDisabledException) {
            return false;
        }

        if ($e instanceof Msg91ApiException) {
            $status = $e->getStatusCode();
            // 4xx errors are client errors — retrying won't help
            if ($status >= 400 && $status < 500) {
                return false;
            }
        }

        return true;
    }
}
