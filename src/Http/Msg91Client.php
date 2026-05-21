<?php

declare(strict_types=1);

namespace Parvion\Msg91\Http;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Parvion\Msg91\Contracts\Msg91ClientInterface;
use Parvion\Msg91\Exceptions\Msg91ApiException;
use Parvion\Msg91\Exceptions\Msg91RateLimitException;
use Parvion\Msg91\Logging\LogDriverManager;
use Parvion\Msg91\Traits\Behaviors\InteractsWithConfig;

/**
 * Class Msg91Client
 *
 * Concrete HTTP client implementation for the MSG91 REST API.
 * Wraps Laravel's Http facade with:
 *   - Automatic authkey header injection
 *   - Base URL resolution from config
 *   - Configurable timeout
 *   - Structured response normalisation
 *   - Error-to-exception mapping (4xx, 5xx, MSG91 error bodies)
 *   - Request/response logging via LogDriverManager
 *
 * This class is intentionally kept stateless. All config values are read
 * fresh on every request so runtime changes (e.g. in tests) take effect.
 *
 * @package Parvion\Msg91\Http
 */
class Msg91Client implements Msg91ClientInterface
{
    use InteractsWithConfig;

    public function __construct(
        private readonly LogDriverManager $logManager,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function get(string $endpoint, array $query = []): array
    {
        $url       = $this->resolveUrl($endpoint);
        $startedAt = hrtime(true);

        try {
            $response = Http::withHeaders($this->buildHeaders())
                ->timeout($this->getTimeout())
                ->get($url, $query);

            $durationMs = $this->elapsed($startedAt);
            $result     = $this->handleResponse($response);

            $this->logManager->logSuccess(
                channel:    'http',
                action:     'GET ' . $endpoint,
                recipient:  $query['mobile'] ?? $query['to'] ?? '',
                request:    $this->maskSensitive(array_merge(['url' => $url], $query)),
                response:   $result,
                httpStatus: $response->status(),
                durationMs: $durationMs,
            );

            return $result;
        } catch (Msg91ApiException $e) {
            $this->logManager->logFailure(
                channel:    'http',
                action:     'GET ' . $endpoint,
                recipient:  $query['mobile'] ?? $query['to'] ?? '',
                request:    $this->maskSensitive(array_merge(['url' => $url], $query)),
                exception:  $e,
                httpStatus: $e->getStatusCode() ?: null,
                durationMs: $this->elapsed($startedAt),
            );

            throw $e;
        } catch (\Throwable $e) {
            $this->logManager->logFailure(
                channel:    'http',
                action:     'GET ' . $endpoint,
                recipient:  $query['mobile'] ?? $query['to'] ?? '',
                request:    $this->maskSensitive(array_merge(['url' => $url], $query)),
                exception:  $e,
                httpStatus: null,
                durationMs: $this->elapsed($startedAt),
            );

            throw new Msg91ApiException(
                "MSG91 HTTP request failed: {$e->getMessage()}",
                0,
                [],
                $e,
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function post(string $endpoint, array $payload = []): array
    {
        $url       = $this->resolveUrl($endpoint);
        $startedAt = hrtime(true);

        try {
            $response = Http::withHeaders($this->buildHeaders())
                ->timeout($this->getTimeout())
                ->post($url, $payload);

            $durationMs = $this->elapsed($startedAt);
            $result     = $this->handleResponse($response);

            $this->logManager->logSuccess(
                channel:    'http',
                action:     'POST ' . $endpoint,
                recipient:  $payload['mobile'] ?? $payload['to'] ?? '',
                request:    $this->maskSensitive($payload),
                response:   $result,
                httpStatus: $response->status(),
                durationMs: $durationMs,
            );

            return $result;
        } catch (Msg91ApiException $e) {
            $this->logManager->logFailure(
                channel:    'http',
                action:     'POST ' . $endpoint,
                recipient:  $payload['mobile'] ?? $payload['to'] ?? '',
                request:    $this->maskSensitive($payload),
                exception:  $e,
                httpStatus: $e->getStatusCode() ?: null,
                durationMs: $this->elapsed($startedAt),
            );

            throw $e;
        } catch (\Throwable $e) {
            $this->logManager->logFailure(
                channel:    'http',
                action:     'POST ' . $endpoint,
                recipient:  $payload['mobile'] ?? $payload['to'] ?? '',
                request:    $this->maskSensitive($payload),
                exception:  $e,
                httpStatus: null,
                durationMs: $this->elapsed($startedAt),
            );

            throw new Msg91ApiException(
                "MSG91 HTTP request failed: {$e->getMessage()}",
                0,
                [],
                $e,
            );
        }
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Build the HTTP headers required for every MSG91 API request.
     *
     * @return array<string, string>
     */
    private function buildHeaders(): array
    {
        return [
            'authkey'      => $this->getAuthKey(),
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }

    /**
     * Resolve a relative endpoint path to an absolute URL.
     *
     * @param  string  $endpoint  e.g. 'otp' or 'otp/verify'
     * @return string             e.g. 'https://api.msg91.com/api/v5/otp'
     */
    private function resolveUrl(string $endpoint): string
    {
        return $this->getBaseUrl() . ltrim($endpoint, '/');
    }

    /**
     * Inspect a HTTP response and either return the normalised payload
     * or throw the appropriate exception.
     *
     * @throws Msg91RateLimitException  On HTTP 429.
     * @throws Msg91ApiException        On any other non-2xx or MSG91 error body.
     */
    private function handleResponse(Response $response): array
    {
        // Rate limit
        if ($response->status() === 429) {
            throw Msg91RateLimitException::fromResponse($response);
        }

        $body = $response->json() ?? [];

        // MSG91 sometimes returns 200 with {"type":"error",...}
        $isErrorBody = isset($body['type']) && strtolower($body['type']) === 'error';

        if (! $response->successful() || $isErrorBody) {
            throw Msg91ApiException::fromResponse($response);
        }

        return $this->normalise($body);
    }

    /**
     * Normalise the MSG91 response body into a consistent shape.
     *
     * Guaranteed output keys:
     *   'type'    — 'success'
     *   'message' — human-readable result string
     *   'data'    — additional response data (array or null)
     */
    private function normalise(array $body): array
    {
        return [
            'type'    => $body['type']    ?? 'success',
            'message' => $body['message'] ?? '',
            'data'    => $body['data']    ?? array_diff_key($body, array_flip(['type', 'message'])),
        ];
    }

    /**
     * Calculate elapsed milliseconds from an hrtime() start point.
     */
    private function elapsed(int $startedAt): int
    {
        return (int) ((hrtime(true) - $startedAt) / 1_000_000);
    }

    /**
     * Mask sensitive fields before they are written to any log.
     */
    private function maskSensitive(array $payload): array
    {
        $sensitive = ['authkey', 'auth_key', 'otp', 'password', 'token'];

        return array_map(
            fn ($key, $value) => in_array(strtolower((string) $key), $sensitive, true)
                ? '***REDACTED***'
                : $value,
            array_keys($payload),
            $payload
        );
    }
}
