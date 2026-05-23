<?php

declare(strict_types=1);

namespace Parvion\Msg91\Exceptions;

use Illuminate\Http\Client\Response;

/**
 * Class Msg91RateLimitException
 *
 * Thrown in two scenarios:
 *
 *   1. MSG91 API returns HTTP 429 Too Many Requests.
 *   2. The local throttle guard (ManagesThrottling) rejects the request
 *      before it is even sent to the API.
 *
 * Check getRetryAfter() to know how many seconds the caller should wait.
 */
class Msg91RateLimitException extends Msg91ApiException
{
    private int $retryAfter;

    public function __construct(
        string $message = 'Rate limit exceeded.',
        int $statusCode = 429,
        array $responseBody = [],
        int $retryAfter = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $responseBody, $previous);
        $this->retryAfter = $retryAfter;
    }

    /**
     * Create from a MSG91 429 HTTP response.
     * Reads the Retry-After header when present.
     */
    public static function fromResponse(Response $response): static
    {
        $body = $response->json() ?? [];
        $message = $body['message'] ?? 'Too many requests. Please try again later.';
        $retryAfter = (int) ($response->header('Retry-After') ?? 60);

        return new static($message, 429, $body, $retryAfter);
    }

    /**
     * Create a rate-limit exception triggered by the local throttle guard
     * (i.e., the request was never sent to MSG91).
     *
     * @param  string  $identifier  The throttle key (e.g. mobile number or IP).
     * @param  int  $retryAfter  Seconds remaining in the throttle window.
     */
    public static function localThrottle(string $identifier, int $retryAfter = 60): static
    {
        return new static(
            "Too many MSG91 requests for [{$identifier}]. Retry after {$retryAfter} seconds.",
            429,
            [],
            $retryAfter,
        );
    }

    /**
     * Number of seconds the caller should wait before retrying.
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
