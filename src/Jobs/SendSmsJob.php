<?php

declare(strict_types=1);

namespace Parvion\Msg91\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Parvion\Msg91\DTOs\SmsData;

/**
 * Class SendSmsJob
 *
 * Queued job that dispatches an SMS via the MSG91 API.
 * Failed jobs fire the MessageFailed event after max retries are exhausted.
 *
 * @package Parvion\Msg91\Jobs
 */
class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // TODO: Phase 4 — constructor(readonly SmsData $smsData), handle(), failed()
}
