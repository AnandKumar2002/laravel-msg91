<?php

declare(strict_types=1);

namespace Parvion\Msg91\Events;

/**
 * Class MessageFailed
 *
 * Fired whenever any MSG91 API call fails (OTP, SMS, Email, or WhatsApp).
 * Carries the channel name, the payload that was sent, and the exception thrown.
 *
 * @package Parvion\Msg91\Events
 */
class MessageFailed
{
    // TODO: Phase 3 — constructor(readonly string $channel, readonly array $payload, readonly \Throwable $exception)
}
