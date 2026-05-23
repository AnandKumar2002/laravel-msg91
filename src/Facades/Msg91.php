<?php

declare(strict_types=1);

namespace Parvion\Msg91\Facades;

use Illuminate\Support\Facades\Facade;
use Parvion\Msg91\Testing\Msg91Fake;
use Parvion\Msg91\Traits\Behaviors\FakesMsg91;

/**
 * Class Msg91
 *
 * Facade providing a clean static API for all MSG91 services.
 * Resolves the Msg91 singleton from the container.
 *
 * ──────────────────────────────────────────────
 * OTP methods (Phase 3)
 * ──────────────────────────────────────────────
 *
 * @method static array sendOtp(\Parvion\Msg91\DTOs\OtpData $data)
 * @method static array verifyOtp(string $mobile, string $otp)
 * @method static array resendOtp(string $mobile)
 * @method static array retryOtp(string $mobile, \Parvion\Msg91\Enums\OtpRetryType $type)
 *
 * ──────────────────────────────────────────────
 * SMS methods (Phase 4)
 * ──────────────────────────────────────────────
 * @method static array sendSms(\Parvion\Msg91\DTOs\SmsData $data)
 * @method static array sendBulkSms(string[] $recipients, \Parvion\Msg91\DTOs\SmsData $data)
 * @method static array checkDeliveryStatus(string $requestId)
 *
 * ──────────────────────────────────────────────
 * Email methods (Phase 5)
 * ──────────────────────────────────────────────
 * @method static array sendEmail(\Parvion\Msg91\DTOs\EmailData $data)
 * @method static array sendBulkEmail(string[] $recipients, \Parvion\Msg91\DTOs\EmailData $data)
 *
 * ──────────────────────────────────────────────
 * WhatsApp methods (Phase 5)
 * ──────────────────────────────────────────────
 * @method static array sendWhatsApp(\Parvion\Msg91\DTOs\WhatsAppData $data)
 * @method static array sendWhatsAppTemplate(\Parvion\Msg91\DTOs\WhatsAppData $data)
 *
 * ──────────────────────────────────────────────
 * Testing helpers (Phase 6)
 * ──────────────────────────────────────────────
 * @method static Msg91Fake fake()
 *
 * ──────────────────────────────────────────────
 * Assertion helpers (via Msg91Fake)
 * ──────────────────────────────────────────────
 * @method static void assertOtpSent(?string $mobile = null)
 * @method static void assertOtpNotSent(?string $mobile = null)
 * @method static void assertSmsSent(?callable $callback = null)
 * @method static void assertSmsNotSent()
 * @method static void assertEmailSent(?callable $callback = null)
 * @method static void assertEmailNotSent()
 * @method static void assertWhatsAppSent(?callable $callback = null)
 * @method static void assertWhatsAppNotSent()
 * @method static void assertNothingSent()
 *
 * ──────────────────────────────────────────────
 * Macros (always available)
 * ──────────────────────────────────────────────
 * @method static void macro(string $name, callable $macro)
 * @method static bool hasMacro(string $name)
 *
 * @see \Parvion\Msg91\Msg91
 */
class Msg91 extends Facade
{
    use FakesMsg91;

    /**
     * Get the registered name of the component in the IoC container.
     *
     * This must match the singleton key registered in Msg91ServiceProvider.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Parvion\Msg91\Msg91::class;
    }
}
