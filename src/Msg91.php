<?php

declare(strict_types=1);

namespace Parvion\Msg91;

use Illuminate\Support\Traits\Macroable;
use Parvion\Msg91\Contracts\Msg91ClientInterface;
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
 * Composes service traits for OTP, SMS, Email, and WhatsApp.
 * Resolved from the container via the Msg91 Facade.
 *
 * @package Parvion\Msg91
 */
class Msg91
{
    use Macroable;
    use ManagesOtp;
    use ManagesSms;
    use ManagesEmail;
    use ManagesWhatsApp;
    use FakesMsg91;
    use InteractsWithConfig;
    use ManagesThrottling;
    use WithRetries;

    // TODO: Phase 1 — constructor(readonly Msg91ClientInterface $client)
}
