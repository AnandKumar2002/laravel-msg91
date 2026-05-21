<?php

declare(strict_types=1);

namespace Parvion\Msg91\Traits\Behaviors;

/**
 * Trait WithRetries
 *
 * Wraps API calls with configurable exponential back-off retry logic.
 * Retries only on transient failures (connection errors, 5xx responses).
 * Respects the Msg91RateLimitException and does NOT retry on 4xx errors.
 *
 * Config keys: msg91.retry.attempts, msg91.retry.sleep_milliseconds
 *
 * @package Parvion\Msg91\Traits\Behaviors
 */
trait WithRetries
{
    // TODO: Phase 2 — withRetry(callable $callback): mixed, isRetryable(\Throwable $e): bool
}
