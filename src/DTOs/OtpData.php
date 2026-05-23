<?php

declare(strict_types=1);

namespace Parvion\Msg91\DTOs;

use InvalidArgumentException;

/**
 * Class OtpData
 *
 * Immutable Data Transfer Object representing an OTP send request.
 * Uses PHP 8.1 readonly constructor promotion for immutability.
 *
 * Usage:
 *   $otp = OtpData::fromArray([
 *       'mobile'      => '919876543210',
 *       'template_id' => 'your-template-id',
 *   ]);
 *
 *   Msg91::sendOtp($otp);
 */
class OtpData
{
    public function __construct(
        /** Mobile number in E.164 format (e.g. '919876543210'). */
        public readonly string $mobile,

        /** MSG91 OTP template ID. Falls back to config if not set. */
        public readonly string $templateId,

        /** Number of digits in the generated OTP (4–8). */
        public readonly int $otpLength = 6,

        /** OTP expiry time in minutes. */
        public readonly int $otpExpiry = 10,

        /**
         * A specific OTP value to send. When null, MSG91 auto-generates one.
         * Use this for deterministic OTPs in controlled environments.
         */
        public readonly ?string $otp = null,

        /** Extra variables for the OTP template (key → value pairs). */
        public readonly array $variables = [],
    ) {
        if (empty($this->mobile)) {
            throw new InvalidArgumentException('OtpData: mobile number must not be empty.');
        }

        if (empty($this->templateId)) {
            throw new InvalidArgumentException('OtpData: template_id must not be empty.');
        }

        if ($this->otpLength < 4 || $this->otpLength > 8) {
            throw new InvalidArgumentException('OtpData: otp_length must be between 4 and 8.');
        }
    }

    /**
     * Construct an OtpData from a plain array.
     *
     * Required keys: mobile, template_id
     * Optional keys: otp_length, otp_expiry, otp, variables
     *
     * @throws InvalidArgumentException If required keys are missing or values are invalid.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            mobile: $data['mobile'] ?? '',
            templateId: $data['template_id'] ?? (string) config('msg91.otp.template_id', ''),
            otpLength: (int) ($data['otp_length'] ?? config('msg91.otp.otp_length', 6)),
            otpExpiry: (int) ($data['otp_expiry'] ?? config('msg91.otp.otp_expiry', 10)),
            otp: isset($data['otp']) ? (string) $data['otp'] : null,
            variables: $data['variables'] ?? [],
        );
    }

    /**
     * Serialise to the MSG91 API request payload format.
     */
    public function toArray(): array
    {
        $payload = [
            'mobile' => $this->mobile,
            'template_id' => $this->templateId,
            'otp_length' => $this->otpLength,
            'otp_expiry' => $this->otpExpiry,
        ];

        if ($this->otp !== null) {
            $payload['otp'] = $this->otp;
        }

        if (! empty($this->variables)) {
            $payload['extra_param'] = json_encode($this->variables);
        }

        return $payload;
    }
}
