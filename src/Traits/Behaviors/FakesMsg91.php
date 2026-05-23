<?php

declare(strict_types=1);

namespace Parvion\Msg91\Traits\Behaviors;

use Parvion\Msg91\Contracts\OtpServiceInterface;
use Parvion\Msg91\Contracts\SmsServiceInterface;
use Parvion\Msg91\Msg91;
use Parvion\Msg91\Testing\Msg91Fake;

/**
 * Trait FakesMsg91
 *
 * Provides a static fake() method that swaps the real Msg91 singleton
 * in the container with an Msg91Fake instance. All subsequent calls
 * through the Facade or container resolution will hit the fake.
 *
 * Usage in tests:
 *
 *   use Parvion\Msg91\Facades\Msg91;
 *
 *   $fake = Msg91::fake();
 *
 *   // ... perform actions that call Msg91 ...
 *
 *   $fake->assertOtpSent('919876543210');
 *   $fake->assertNothingSent();
 *
 * The fake is automatically registered for:
 *   - Msg91::class
 *   - OtpServiceInterface::class
 *   - SmsServiceInterface::class
 */
trait FakesMsg91
{
    /**
     * Swap the Msg91 singleton with a test fake and return it.
     *
     * The fake is registered as the singleton for all three bindings
     * so that both Facade usage and dependency injection resolve to
     * the same Msg91Fake instance.
     *
     * @return Msg91Fake The fake instance — call assertion methods on it.
     */
    public static function fake(): Msg91Fake
    {
        $fake = new Msg91Fake;

        // Replace the Msg91 singleton binding
        app()->instance(Msg91::class, $fake);

        // Also replace the interface bindings so type-hinted DI works
        app()->instance(OtpServiceInterface::class, $fake);
        app()->instance(SmsServiceInterface::class, $fake);

        return $fake;
    }
}
