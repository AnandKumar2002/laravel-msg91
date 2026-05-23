<?php

declare(strict_types=1);

namespace Parvion\Msg91\Exceptions;

use Illuminate\Http\Client\Response;

/**
 * Class InvalidOtpException
 *
 * Thrown when OTP verification fails due to an invalid code.
 * Provides named constructors for each failure scenario so callers
 * can give the user meaningful, specific feedback.
 *
 * Usage:
 *   try {
 *       Msg91::verifyOtp($mobile, $otp);
 *   } catch (InvalidOtpException $e) {
 *       // $e->isExpired(), $e->isIncorrect(), $e->isAlreadyUsed()
 *   }
 */
class InvalidOtpException extends Msg91ApiException
{
    private const REASON_EXPIRED = 'expired';

    private const REASON_INCORRECT = 'incorrect';

    private const REASON_ALREADY_USED = 'already_used';

    public function __construct(
        string $message = 'Invalid OTP.',
        int $statusCode = 400,
        array $responseBody = [],
        private readonly string $reason = self::REASON_INCORRECT,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $responseBody, $previous);
    }

    // ── Named constructors ─────────────────────────────────────────────────────

    /**
     * The OTP has passed its expiry window.
     */
    public static function expired(?\Throwable $previous = null): static
    {
        return new static(
            'The OTP has expired. Please request a new one.',
            400,
            [],
            self::REASON_EXPIRED,
            $previous,
        );
    }

    /**
     * The OTP was entered incorrectly (does not match).
     */
    public static function incorrect(?\Throwable $previous = null): static
    {
        return new static(
            'The OTP entered is incorrect. Please try again.',
            422,
            [],
            self::REASON_INCORRECT,
            $previous,
        );
    }

    /**
     * The OTP has already been verified and cannot be reused.
     */
    public static function alreadyUsed(?\Throwable $previous = null): static
    {
        return new static(
            'This OTP has already been used.',
            409,
            [],
            self::REASON_ALREADY_USED,
            $previous,
        );
    }

    /**
     * Parse the MSG91 API error response and return the most specific subtype.
     */
    public static function fromResponse(Response $response): static
    {
        $body = $response->json() ?? [];
        $message = strtolower($body['message'] ?? '');

        if (str_contains($message, 'expir')) {
            return static::expired();
        }

        if (str_contains($message, 'already') || str_contains($message, 'verified')) {
            return static::alreadyUsed();
        }

        // Default: incorrect / not matched
        return static::incorrect();
    }

    // ── State predicates ───────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->reason === self::REASON_EXPIRED;
    }

    public function isIncorrect(): bool
    {
        return $this->reason === self::REASON_INCORRECT;
    }

    public function isAlreadyUsed(): bool
    {
        return $this->reason === self::REASON_ALREADY_USED;
    }

    /**
     * Return the machine-readable failure reason string.
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
