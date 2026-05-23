<?php

declare(strict_types=1);

namespace Parvion\Msg91\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * Class Msg91ApiException
 *
 * Thrown when the MSG91 API returns an error response — either a non-2xx
 * HTTP status code, or a successful HTTP status with `"type":"error"` in the body.
 */
class Msg91ApiException extends RuntimeException
{
    public function __construct(
        string $message = '',
        private readonly int $statusCode = 0,
        private readonly array $responseBody = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * Create an exception from a failed Laravel HTTP client response.
     *
     * Parses the JSON body for MSG91's `message` field, falling back to
     * the HTTP status text if the body cannot be decoded.
     */
    public static function fromResponse(Response $response): static
    {
        $body = $response->json() ?? [];
        $message = $body['message'] ?? $response->reason() ?? 'Unknown MSG91 API error';
        $statusCode = $response->status();

        return new static($message, $statusCode, $body);
    }

    /**
     * The HTTP status code returned by MSG91 (0 if not from an HTTP response).
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * The raw decoded JSON body returned by MSG91.
     */
    public function getResponseBody(): array
    {
        return $this->responseBody;
    }
}
