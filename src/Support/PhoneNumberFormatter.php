<?php

declare(strict_types=1);

namespace Parvion\Msg91\Support;

use InvalidArgumentException;

/**
 * Class PhoneNumberFormatter
 *
 * Normalises phone numbers for the MSG91 API.
 * MSG91 expects numbers in E.164 format WITHOUT the leading plus sign.
 * e.g. '919876543210' (not '+919876543210' or '09876543210')
 *
 * Examples:
 *   '9876543210'     → '919876543210'  (country code prepended)
 *   '+919876543210'  → '919876543210'  (plus stripped)
 *   '09876543210'    → '919876543210'  (leading 0 stripped, code prepended)
 *   '919876543210'   → '919876543210'  (already correct, passthrough)
 *
 * @package Parvion\Msg91\Support
 */
class PhoneNumberFormatter
{
    /**
     * Minimum length of a local number (without country code) in digits.
     */
    private const MIN_LOCAL_DIGITS = 7;

    /**
     * Maximum total length including country code.
     */
    private const MAX_TOTAL_DIGITS = 15;

    public function __construct(
        private readonly string $defaultCountryCode = '91',
    ) {}

    /**
     * Format a phone number into MSG91-ready format (E.164 without the +).
     *
     * @param  string  $mobile          Raw phone number in any common format.
     * @param  string|null  $countryCode Override country code (default from config).
     * @return string                   Formatted number: '{countryCode}{localNumber}'.
     *
     * @throws InvalidArgumentException  If the number cannot be normalised.
     */
    public function format(string $mobile, ?string $countryCode = null): string
    {
        $countryCode = $countryCode ?? $this->defaultCountryCode;
        $cleaned     = $this->clean($mobile);

        if (! $this->isValidCleaned($cleaned)) {
            throw new InvalidArgumentException(
                "Cannot format phone number [{$mobile}]: invalid format."
            );
        }

        // Already has the correct country code prefix — return as-is
        if (str_starts_with($cleaned, $countryCode)) {
            $localPart = substr($cleaned, strlen($countryCode));

            // Confirm the remainder is a plausible local number
            if (strlen($localPart) >= self::MIN_LOCAL_DIGITS) {
                return $cleaned;
            }
        }

        return $countryCode . $cleaned;
    }

    /**
     * Format and validate — returns null instead of throwing on invalid input.
     */
    public function tryFormat(string $mobile, ?string $countryCode = null): ?string
    {
        try {
            return $this->format($mobile, $countryCode);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Validate a phone number without formatting it.
     * Returns true if the number can be successfully normalised.
     */
    public function isValid(string $mobile): bool
    {
        return $this->tryFormat($mobile) !== null;
    }

    /**
     * Strip the country code prefix from a fully-qualified number.
     *
     * @param  string  $mobile       E.g. '919876543210'
     * @param  string|null  $countryCode  Country code to strip (default from config).
     * @return string                 Local number: '9876543210'
     */
    public function stripCountryCode(string $mobile, ?string $countryCode = null): string
    {
        $countryCode = $countryCode ?? $this->defaultCountryCode;
        $cleaned     = $this->clean($mobile);

        if (str_starts_with($cleaned, $countryCode)) {
            return substr($cleaned, strlen($countryCode));
        }

        return $cleaned;
    }

    /**
     * Prepend the country code if it is not already present.
     *
     * @param  string  $mobile
     * @param  string|null  $countryCode
     * @return string
     */
    public function prependCountryCode(string $mobile, ?string $countryCode = null): string
    {
        $countryCode = $countryCode ?? $this->defaultCountryCode;
        $cleaned     = $this->clean($mobile);

        if (str_starts_with($cleaned, $countryCode)) {
            return $cleaned;
        }

        return $countryCode . $cleaned;
    }

    /**
     * Format an array of mobile numbers.
     * Invalid numbers are silently skipped.
     *
     * @param  string[]  $mobiles
     * @param  string|null  $countryCode
     * @return string[]
     */
    public function formatMany(array $mobiles, ?string $countryCode = null): array
    {
        return array_values(
            array_filter(
                array_map(fn ($m) => $this->tryFormat($m, $countryCode), $mobiles)
            )
        );
    }

    /**
     * Strip all non-digit characters and remove a leading plus sign.
     * Also strips a leading zero (common in local dialling formats).
     */
    private function clean(string $mobile): string
    {
        // Remove everything except digits
        $digits = preg_replace('/\D/', '', $mobile);

        // Remove leading zero (e.g. 09876543210 → 9876543210)
        if (str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');
        }

        return $digits;
    }

    /**
     * Check if a cleaned (digits-only) number is plausibly valid.
     */
    private function isValidCleaned(string $digits): bool
    {
        $length = strlen($digits);

        return $length >= self::MIN_LOCAL_DIGITS
            && $length <= self::MAX_TOTAL_DIGITS;
    }
}
