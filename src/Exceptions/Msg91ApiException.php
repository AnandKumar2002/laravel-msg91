<?php

declare(strict_types=1);

namespace Parvion\Msg91\Exceptions;

use RuntimeException;

/**
 * Class Msg91ApiException
 *
 * Thrown when the MSG91 API returns an error response (non-2xx status,
 * or a `type=error` body). Carries the HTTP status code and raw response body.
 *
 * @package Parvion\Msg91\Exceptions
 */
class Msg91ApiException extends RuntimeException
{
    // TODO: Phase 2 — constructor, static fromResponse() factory, getStatusCode(), getResponseBody()
}
