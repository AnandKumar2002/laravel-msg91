<?php

declare(strict_types=1);

namespace Parvion\Msg91\Exceptions;

/**
 * Class Msg91RateLimitException
 *
 * Thrown when the MSG91 API returns a 429 Too Many Requests response,
 * or when the local throttle guard rejects a request before it is sent.
 *
 * @package Parvion\Msg91\Exceptions
 */
class Msg91RateLimitException extends Msg91ApiException
{
    // TODO: Phase 2 — retryAfter property, getRetryAfter()
}
