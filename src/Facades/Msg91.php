<?php

declare(strict_types=1);

namespace Parvion\Msg91\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Msg91
 *
 * Facade providing a clean static API for all MSG91 services.
 *
 * @method static mixed sendOtp(\Parvion\Msg91\DTOs\OtpData $data)
 * @method static mixed verifyOtp(string $mobile, string $otp)
 * @method static mixed resendOtp(string $mobile)
 * @method static mixed retryOtp(string $mobile, \Parvion\Msg91\Enums\OtpRetryType $type)
 * @method static mixed sendSms(\Parvion\Msg91\DTOs\SmsData $data)
 * @method static mixed sendBulkSms(array $recipients, \Parvion\Msg91\DTOs\SmsData $data)
 * @method static mixed sendEmail(\Parvion\Msg91\DTOs\EmailData $data)
 * @method static mixed sendWhatsApp(\Parvion\Msg91\DTOs\WhatsAppData $data)
 * @method static \Parvion\Msg91\Testing\Msg91Fake fake()
 *
 * @see \Parvion\Msg91\Msg91
 *
 * @package Parvion\Msg91\Facades
 */
class Msg91 extends Facade
{
    /**
     * Get the registered name of the component in the container.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Parvion\Msg91\Msg91::class;
    }
}
