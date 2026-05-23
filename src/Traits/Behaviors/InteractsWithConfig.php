<?php

declare(strict_types=1);

namespace Parvion\Msg91\Traits\Behaviors;

/**
 * Trait InteractsWithConfig
 *
 * Provides strongly-typed, IDE-friendly accessors for every key in the
 * msg91 config file. Mixed into Msg91Client, Msg91 (via the main class),
 * and any service trait that needs direct config access.
 *
 * All methods read live from the Laravel config so that runtime changes
 * (e.g. in tests via config()->set()) are reflected immediately.
 */
trait InteractsWithConfig
{
    // ── Core ──────────────────────────────────────────────────────────────────

    /**
     * The MSG91 authentication key.
     */
    public function getAuthKey(): string
    {
        return (string) config('msg91.auth_key', '');
    }

    /**
     * The MSG91 REST API base URL (trailing slash included).
     */
    public function getBaseUrl(): string
    {
        $url = (string) config('msg91.base_url', 'https://api.msg91.com/api/v5/');

        return rtrim($url, '/').'/';
    }

    /**
     * HTTP request timeout in seconds.
     */
    public function getTimeout(): int
    {
        return (int) config('msg91.timeout', 30);
    }

    /**
     * The default SMS sender ID.
     */
    public function getSenderId(): string
    {
        return (string) config('msg91.sender_id', '');
    }

    /**
     * Default country code prepended when no country code is present (e.g. '91').
     */
    public function getDefaultCountryCode(): string
    {
        return (string) config('msg91.default_country_code', '91');
    }

    // ── Feature Flags ─────────────────────────────────────────────────────────

    /**
     * Check if a specific MSG91 channel is enabled.
     *
     * @param  string  $feature  One of: 'otp', 'sms', 'email', 'whatsapp'.
     */
    public function isFeatureEnabled(string $feature): bool
    {
        return (bool) config("msg91.features.{$feature}", true);
    }

    // ── Retry ─────────────────────────────────────────────────────────────────

    /**
     * Number of retry attempts before giving up on a transient failure.
     */
    public function getRetryAttempts(): int
    {
        return max(1, (int) config('msg91.retry.attempts', 3));
    }

    /**
     * Base sleep duration between retries in milliseconds.
     * Each attempt doubles this value (exponential back-off).
     */
    public function getRetrySleepMs(): int
    {
        return max(0, (int) config('msg91.retry.sleep_milliseconds', 200));
    }

    // ── Throttle ──────────────────────────────────────────────────────────────

    /**
     * Maximum API requests allowed within the throttle window.
     */
    public function getThrottleMaxAttempts(): int
    {
        return max(1, (int) config('msg91.throttle.max_attempts', 60));
    }

    /**
     * Throttle window duration in seconds.
     */
    public function getThrottleDecaySeconds(): int
    {
        return max(1, (int) config('msg91.throttle.decay_seconds', 60));
    }

    // ── Logging ───────────────────────────────────────────────────────────────

    /**
     * Active logging driver name: 'null', 'log', 'database', or 'stack'.
     */
    public function getLogDriver(): string
    {
        return (string) config('msg91.logging.driver', 'null');
    }

    /**
     * Laravel log channel name. Null means the default application channel.
     */
    public function getLogChannel(): ?string
    {
        $channel = config('msg91.logging.channel');

        return $channel !== null ? (string) $channel : null;
    }

    /**
     * Log level used by the channel driver: 'debug', 'info', 'warning', 'error'.
     */
    public function getLogLevel(): string
    {
        return (string) config('msg91.logging.level', 'info');
    }

    // ── OTP ───────────────────────────────────────────────────────────────────

    /**
     * Default OTP template ID (can be overridden per-request via OtpData).
     */
    public function getOtpTemplateId(): string
    {
        return (string) config('msg91.otp.template_id', '');
    }

    /**
     * Default OTP length in digits (4 or 6 are most common).
     */
    public function getOtpLength(): int
    {
        return (int) config('msg91.otp.otp_length', 6);
    }

    /**
     * Default OTP expiry in minutes.
     */
    public function getOtpExpiry(): int
    {
        return (int) config('msg91.otp.otp_expiry', 10);
    }

    // ── SMS ───────────────────────────────────────────────────────────────────

    /**
     * Default SMS route integer (e.g. 4 = Transactional).
     */
    public function getSmsRoute(): int
    {
        return (int) config('msg91.sms.route', 4);
    }

    // ── Queue ─────────────────────────────────────────────────────────────────

    /**
     * Queue connection for SendSmsJob. Null means the default connection.
     */
    public function getQueueConnection(): ?string
    {
        $connection = config('msg91.queue.connection');

        return $connection !== null ? (string) $connection : null;
    }

    /**
     * Queue name for SendSmsJob.
     */
    public function getQueueName(): string
    {
        return (string) config('msg91.queue.queue', 'default');
    }
}
