<?php

declare(strict_types=1);

namespace Parvion\Msg91\Contracts;

use Parvion\Msg91\Exceptions\Msg91ApiException;
use Parvion\Msg91\Exceptions\Msg91RateLimitException;

/**
 * Interface Msg91ClientInterface
 *
 * Defines the contract for the low-level HTTP client that communicates
 * with the MSG91 REST API. Implementations must be swappable (e.g. for fakes).
 *
 * All methods return a normalised response array:
 * [
 *   'type'    => 'success'|'error',
 *   'message' => string,
 *   'data'    => array|null,
 * ]
 *
 * @throws Msg91ApiException       On non-2xx responses or error body from MSG91.
 * @throws Msg91RateLimitException On 429 responses or local throttle rejection.
 *
 * @package Parvion\Msg91\Contracts
 */
interface Msg91ClientInterface
{
    /**
     * Perform a GET request against the MSG91 API.
     *
     * @param  string  $endpoint  Relative endpoint path (e.g. 'otp/verify').
     * @param  array   $query     URL query parameters to append.
     * @return array              Normalised response array.
     *
     * @throws Msg91ApiException
     * @throws Msg91RateLimitException
     */
    public function get(string $endpoint, array $query = []): array;

    /**
     * Perform a POST request against the MSG91 API with a JSON body.
     *
     * @param  string  $endpoint  Relative endpoint path (e.g. 'otp').
     * @param  array   $payload   Request body — will be JSON-encoded.
     * @return array              Normalised response array.
     *
     * @throws Msg91ApiException
     * @throws Msg91RateLimitException
     */
    public function post(string $endpoint, array $payload = []): array;
}
