<?php

declare(strict_types=1);

namespace Parvion\Msg91\Testing;

use Parvion\Msg91\Contracts\OtpServiceInterface;
use Parvion\Msg91\Contracts\SmsServiceInterface;

/**
 * Class Msg91Fake
 *
 * In-memory implementation of all MSG91 service contracts for use in tests.
 * Swap in via Msg91::fake() — no real HTTP calls are made.
 *
 * Provides assertion helpers:
 *   assertOtpSent(string $mobile)
 *   assertSmsSent(string $mobile)
 *   assertNothingSent()
 *
 * @package Parvion\Msg91\Testing
 */
class Msg91Fake implements OtpServiceInterface, SmsServiceInterface
{
    // TODO: Phase 6 — in-memory call records, assertion methods, interface implementations
}
