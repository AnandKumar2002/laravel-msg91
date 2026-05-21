<?php

declare(strict_types=1);

namespace Parvion\Msg91\Traits\Behaviors;

/**
 * Trait FakesMsg91
 *
 * Adds the static fake() method to the Msg91 class, allowing tests to swap
 * the real service implementation for Msg91Fake — mirroring Laravel's
 * Http::fake() and Mail::fake() developer experience.
 *
 * Usage:
 *   Msg91::fake();
 *   Msg91::sendOtp($data);
 *   Msg91::assertOtpSent('919876543210');
 *
 * @package Parvion\Msg91\Traits\Behaviors
 */
trait FakesMsg91
{
    // TODO: Phase 6 — static fake(): Msg91Fake, swaps container binding
}
