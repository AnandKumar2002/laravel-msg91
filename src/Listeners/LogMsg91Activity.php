<?php

declare(strict_types=1);

namespace Parvion\Msg91\Listeners;

/**
 * Class LogMsg91Activity
 *
 * Listens to OtpSent and MessageFailed events and writes a structured
 * log record to the msg91_logs database table (when activity logging is enabled).
 *
 * @package Parvion\Msg91\Listeners
 */
class LogMsg91Activity
{
    // TODO: Phase 7 — handle(OtpSent|MessageFailed $event), resolves Msg91Logger, writes to DB
}
