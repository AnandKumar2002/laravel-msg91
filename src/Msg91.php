<?php

declare(strict_types=1);

namespace Parvion\Msg91;

use Illuminate\Support\Traits\Macroable;
use Parvion\Msg91\Contracts\Msg91ClientInterface;
use Parvion\Msg91\Contracts\OtpServiceInterface;
use Parvion\Msg91\Contracts\SmsServiceInterface;
use Parvion\Msg91\Logging\LogDriverManager;
use Parvion\Msg91\Traits\Api\ManagesEmail;
use Parvion\Msg91\Traits\Api\ManagesOtp;
use Parvion\Msg91\Traits\Api\ManagesSms;
use Parvion\Msg91\Traits\Api\ManagesWhatsApp;
use Parvion\Msg91\Traits\Behaviors\FakesMsg91;
use Parvion\Msg91\Traits\Behaviors\InteractsWithConfig;
use Parvion\Msg91\Traits\Behaviors\ManagesThrottling;
use Parvion\Msg91\Traits\Behaviors\WithRetries;

/**
 * Class Msg91
 *
 * Main entry point for all MSG91 API operations.
 * Resolved from the container via the Msg91 Facade.
 *
 * Composes service traits for OTP, SMS, Email, and WhatsApp, plus
 * cross-cutting behavior traits for retries, throttling, config access,
 * and fake-swapping in tests.
 *
 * Container binding (set in Msg91ServiceProvider):
 *   app(Msg91::class)               → singleton Msg91 instance
 *   app(OtpServiceInterface::class) → same singleton ✅ (Phase 3 — ManagesOtp)
 *   app(SmsServiceInterface::class) → same singleton ✅ (Phase 4 — ManagesSms)
 */
class Msg91 implements OtpServiceInterface, SmsServiceInterface
{
    use FakesMsg91;
    use InteractsWithConfig;
    use Macroable;
    use ManagesEmail;
    use ManagesOtp;
    use ManagesSms;
    use ManagesThrottling;
    use ManagesWhatsApp;
    use WithRetries;

    /**
     * Create a new Msg91 instance.
     *
     * @param  Msg91ClientInterface  $client  The HTTP client bound in the container.
     * @param  LogDriverManager  $logManager  Resolved log driver (null|log|database|stack).
     */
    public function __construct(
        protected readonly Msg91ClientInterface $client,
        protected readonly LogDriverManager $logManager,
    ) {}
}
