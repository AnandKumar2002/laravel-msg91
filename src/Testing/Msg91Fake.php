<?php

declare(strict_types=1);

namespace Parvion\Msg91\Testing;

use Parvion\Msg91\Contracts\OtpServiceInterface;
use Parvion\Msg91\Contracts\SmsServiceInterface;
use Parvion\Msg91\DTOs\EmailData;
use Parvion\Msg91\DTOs\OtpData;
use Parvion\Msg91\DTOs\SmsData;
use Parvion\Msg91\DTOs\WhatsAppData;
use Parvion\Msg91\Enums\OtpRetryType;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * Class Msg91Fake
 *
 * A test double that records all MSG91 API calls in-memory without
 * making any real HTTP requests. Swap it into the container with:
 *
 *   Msg91::fake();
 *
 * Then assert calls were made:
 *
 *   Msg91::assertOtpSent('919876543210');
 *   Msg91::assertSmsSent();
 *   Msg91::assertNothingSent();
 *
 * The fake implements both OtpServiceInterface and SmsServiceInterface
 * so it can be type-hinted in your application code.
 *
 * @package Parvion\Msg91\Testing
 */
class Msg91Fake implements OtpServiceInterface, SmsServiceInterface
{
    // ── In-memory call stores ──────────────────────────────────────────────────

    /** @var array<int, array{data: OtpData, mobile: string}> */
    protected array $otpSent = [];

    /** @var array<int, array{mobile: string, otp: string}> */
    protected array $otpVerified = [];

    /** @var array<int, array{mobile: string}> */
    protected array $otpResent = [];

    /** @var array<int, array{mobile: string, type: OtpRetryType}> */
    protected array $otpRetried = [];

    /** @var array<int, array{data: SmsData}> */
    protected array $smsSent = [];

    /** @var array<int, array{recipients: string[], data: SmsData}> */
    protected array $bulkSmsSent = [];

    /** @var array<int, string> */
    protected array $deliveryStatusChecked = [];

    /** @var array<int, array{data: EmailData}> */
    protected array $emailSent = [];

    /** @var array<int, array{recipients: string[], data: EmailData}> */
    protected array $bulkEmailSent = [];

    /** @var array<int, array{data: WhatsAppData}> */
    protected array $whatsAppSent = [];

    // ── Configurable response ──────────────────────────────────────────────────

    /** The fake response returned by all API methods. */
    protected array $fakeResponse = [
        'type'    => 'success',
        'message' => 'Fake response from Msg91Fake.',
        'data'    => [],
    ];

    /**
     * Override the default fake response returned by all methods.
     *
     * @return $this
     */
    public function respondWith(array $response): static
    {
        $this->fakeResponse = $response;

        return $this;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  OTP Service Implementation
    // ═══════════════════════════════════════════════════════════════════════════

    public function sendOtp(OtpData $data): array
    {
        $this->otpSent[] = [
            'data'   => $data,
            'mobile' => $data->mobile,
        ];

        return $this->fakeResponse;
    }

    public function verifyOtp(string $mobile, string $otp): array
    {
        $this->otpVerified[] = [
            'mobile' => $mobile,
            'otp'    => $otp,
        ];

        return $this->fakeResponse;
    }

    public function resendOtp(string $mobile): array
    {
        $this->otpResent[] = [
            'mobile' => $mobile,
        ];

        return $this->fakeResponse;
    }

    public function retryOtp(string $mobile, OtpRetryType $type): array
    {
        $this->otpRetried[] = [
            'mobile' => $mobile,
            'type'   => $type,
        ];

        return $this->fakeResponse;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  SMS Service Implementation
    // ═══════════════════════════════════════════════════════════════════════════

    public function sendSms(SmsData $data): array
    {
        $this->smsSent[] = [
            'data' => $data,
        ];

        return $this->fakeResponse;
    }

    public function sendBulkSms(array $recipients, SmsData $data): array
    {
        $this->bulkSmsSent[] = [
            'recipients' => $recipients,
            'data'       => $data,
        ];

        return $this->fakeResponse;
    }

    public function checkDeliveryStatus(string $requestId): array
    {
        $this->deliveryStatusChecked[] = $requestId;

        return $this->fakeResponse;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  Email (non-contract — called via Facade)
    // ═══════════════════════════════════════════════════════════════════════════

    public function sendEmail(EmailData $data): array
    {
        $this->emailSent[] = [
            'data' => $data,
        ];

        return $this->fakeResponse;
    }

    public function sendBulkEmail(array $recipients, EmailData $data): array
    {
        $this->bulkEmailSent[] = [
            'recipients' => $recipients,
            'data'       => $data,
        ];

        return $this->fakeResponse;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  WhatsApp (non-contract — called via Facade)
    // ═══════════════════════════════════════════════════════════════════════════

    public function sendWhatsApp(WhatsAppData $data): array
    {
        $this->whatsAppSent[] = [
            'data' => $data,
        ];

        return $this->fakeResponse;
    }

    public function sendWhatsAppTemplate(WhatsAppData $data): array
    {
        return $this->sendWhatsApp($data);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    //  Assertion Helpers
    // ═══════════════════════════════════════════════════════════════════════════

    // ── OTP Assertions ─────────────────────────────────────────────────────────

    /**
     * Assert that at least one OTP was sent, optionally to a specific mobile.
     */
    public function assertOtpSent(?string $mobile = null): void
    {
        if ($mobile !== null) {
            $found = collect($this->otpSent)->contains('mobile', $mobile);
            PHPUnit::assertTrue($found, "No OTP was sent to [{$mobile}].");
        } else {
            PHPUnit::assertNotEmpty($this->otpSent, 'No OTP was sent.');
        }
    }

    /**
     * Assert that no OTP was sent, optionally to a specific mobile.
     */
    public function assertOtpNotSent(?string $mobile = null): void
    {
        if ($mobile !== null) {
            $found = collect($this->otpSent)->contains('mobile', $mobile);
            PHPUnit::assertFalse($found, "An OTP was unexpectedly sent to [{$mobile}].");
        } else {
            PHPUnit::assertEmpty($this->otpSent, 'OTPs were unexpectedly sent.');
        }
    }

    /**
     * Assert that an OTP was sent exactly N times.
     */
    public function assertOtpSentTimes(int $count): void
    {
        PHPUnit::assertCount(
            $count,
            $this->otpSent,
            "Expected {$count} OTP send(s), but got " . count($this->otpSent) . '.'
        );
    }

    /**
     * Assert OTP was verified for a specific mobile.
     */
    public function assertOtpVerified(?string $mobile = null): void
    {
        if ($mobile !== null) {
            $found = collect($this->otpVerified)->contains('mobile', $mobile);
            PHPUnit::assertTrue($found, "No OTP was verified for [{$mobile}].");
        } else {
            PHPUnit::assertNotEmpty($this->otpVerified, 'No OTP was verified.');
        }
    }

    /**
     * Assert OTP was resent to a specific mobile.
     */
    public function assertOtpResent(?string $mobile = null): void
    {
        if ($mobile !== null) {
            $found = collect($this->otpResent)->contains('mobile', $mobile);
            PHPUnit::assertTrue($found, "No OTP was resent to [{$mobile}].");
        } else {
            PHPUnit::assertNotEmpty($this->otpResent, 'No OTP was resent.');
        }
    }

    /**
     * Assert OTP was retried via a specific channel.
     */
    public function assertOtpRetried(?string $mobile = null, ?OtpRetryType $type = null): void
    {
        $collection = collect($this->otpRetried);

        if ($mobile !== null) {
            $collection = $collection->where('mobile', $mobile);
        }

        if ($type !== null) {
            $collection = $collection->where('type', $type);
        }

        PHPUnit::assertTrue($collection->isNotEmpty(), 'No matching OTP retry was recorded.');
    }

    // ── SMS Assertions ─────────────────────────────────────────────────────────

    /**
     * Assert that at least one SMS was sent.
     */
    public function assertSmsSent(?callable $callback = null): void
    {
        PHPUnit::assertNotEmpty($this->smsSent, 'No SMS was sent.');

        if ($callback !== null) {
            $found = collect($this->smsSent)->contains(fn ($record) => $callback($record['data']));
            PHPUnit::assertTrue($found, 'No SMS matching the callback was found.');
        }
    }

    /**
     * Assert that no SMS was sent.
     */
    public function assertSmsNotSent(): void
    {
        PHPUnit::assertEmpty($this->smsSent, 'SMS messages were unexpectedly sent.');
    }

    /**
     * Assert that a bulk SMS was sent.
     */
    public function assertBulkSmsSent(?int $recipientCount = null): void
    {
        PHPUnit::assertNotEmpty($this->bulkSmsSent, 'No bulk SMS was sent.');

        if ($recipientCount !== null) {
            $found = collect($this->bulkSmsSent)
                ->contains(fn ($record) => count($record['recipients']) === $recipientCount);
            PHPUnit::assertTrue(
                $found,
                "No bulk SMS with exactly {$recipientCount} recipients was found."
            );
        }
    }

    /**
     * Assert that a delivery status check was performed.
     */
    public function assertDeliveryStatusChecked(?string $requestId = null): void
    {
        if ($requestId !== null) {
            PHPUnit::assertContains($requestId, $this->deliveryStatusChecked,
                "Delivery status was not checked for [{$requestId}]."
            );
        } else {
            PHPUnit::assertNotEmpty($this->deliveryStatusChecked, 'No delivery status was checked.');
        }
    }

    // ── Email Assertions ───────────────────────────────────────────────────────

    /**
     * Assert that at least one email was sent.
     */
    public function assertEmailSent(?callable $callback = null): void
    {
        PHPUnit::assertNotEmpty($this->emailSent, 'No email was sent.');

        if ($callback !== null) {
            $found = collect($this->emailSent)->contains(fn ($r) => $callback($r['data']));
            PHPUnit::assertTrue($found, 'No email matching the callback was found.');
        }
    }

    /**
     * Assert that no email was sent.
     */
    public function assertEmailNotSent(): void
    {
        PHPUnit::assertEmpty($this->emailSent, 'Emails were unexpectedly sent.');
    }

    // ── WhatsApp Assertions ────────────────────────────────────────────────────

    /**
     * Assert that at least one WhatsApp message was sent.
     */
    public function assertWhatsAppSent(?callable $callback = null): void
    {
        PHPUnit::assertNotEmpty($this->whatsAppSent, 'No WhatsApp message was sent.');

        if ($callback !== null) {
            $found = collect($this->whatsAppSent)->contains(fn ($r) => $callback($r['data']));
            PHPUnit::assertTrue($found, 'No WhatsApp message matching the callback was found.');
        }
    }

    /**
     * Assert that no WhatsApp message was sent.
     */
    public function assertWhatsAppNotSent(): void
    {
        PHPUnit::assertEmpty($this->whatsAppSent, 'WhatsApp messages were unexpectedly sent.');
    }

    // ── Global Assertions ──────────────────────────────────────────────────────

    /**
     * Assert that nothing was sent on any channel.
     */
    public function assertNothingSent(): void
    {
        PHPUnit::assertEmpty($this->otpSent, 'OTPs were unexpectedly sent.');
        PHPUnit::assertEmpty($this->smsSent, 'SMS messages were unexpectedly sent.');
        PHPUnit::assertEmpty($this->bulkSmsSent, 'Bulk SMS messages were unexpectedly sent.');
        PHPUnit::assertEmpty($this->emailSent, 'Emails were unexpectedly sent.');
        PHPUnit::assertEmpty($this->bulkEmailSent, 'Bulk emails were unexpectedly sent.');
        PHPUnit::assertEmpty($this->whatsAppSent, 'WhatsApp messages were unexpectedly sent.');
    }

    // ── Introspection ──────────────────────────────────────────────────────────

    /**
     * Return all recorded OTP sends.
     */
    public function getOtpSent(): array
    {
        return $this->otpSent;
    }

    /**
     * Return all recorded SMS sends.
     */
    public function getSmsSent(): array
    {
        return $this->smsSent;
    }

    /**
     * Return all recorded email sends.
     */
    public function getEmailSent(): array
    {
        return $this->emailSent;
    }

    /**
     * Return all recorded WhatsApp sends.
     */
    public function getWhatsAppSent(): array
    {
        return $this->whatsAppSent;
    }

    /**
     * Reset all recorded calls (useful between test methods).
     *
     * @return $this
     */
    public function reset(): static
    {
        $this->otpSent              = [];
        $this->otpVerified          = [];
        $this->otpResent            = [];
        $this->otpRetried           = [];
        $this->smsSent              = [];
        $this->bulkSmsSent          = [];
        $this->deliveryStatusChecked = [];
        $this->emailSent            = [];
        $this->bulkEmailSent        = [];
        $this->whatsAppSent         = [];

        return $this;
    }
}
