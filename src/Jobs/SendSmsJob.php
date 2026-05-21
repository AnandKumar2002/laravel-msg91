<?php

declare(strict_types=1);

namespace Parvion\Msg91\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Parvion\Msg91\DTOs\SmsData;
use Parvion\Msg91\Events\MessageFailed;
use Parvion\Msg91\Facades\Msg91;

/**
 * Class SendSmsJob
 *
 * A queueable job that dispatches a single or bulk SMS via Msg91::sendSms().
 * Allows SMS delivery to be deferred to a background worker, keeping your
 * request lifecycle fast.
 *
 * Usage:
 *   SendSmsJob::dispatch(SmsData::fromArray([
 *       'mobile'  => '919876543210',
 *       'message' => 'Your order has shipped!',
 *   ]));
 *
 *   // With custom queue
 *   SendSmsJob::dispatch($smsData)->onQueue('sms');
 *
 * Queue configuration:
 *   Connection and queue name default to the values in msg91.queue.connection
 *   and msg91.queue.queue. Override per-dispatch via ->onConnection() / ->onQueue().
 *
 * Retries:
 *   The job itself has $tries = 3 with a 30-second backoff. This is in
 *   ADDITION to the WithRetries logic inside the Msg91Client. If all
 *   job-level retries are exhausted, the failed() method fires a
 *   MessageFailed event so listeners can alert or log the final failure.
 *
 * @package Parvion\Msg91\Jobs
 */
class SendSmsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Number of times the job may be attempted before failing permanently.
     */
    public int $tries = 3;

    /**
     * Number of seconds to wait before retrying the job.
     * Each retry waits: 30s → 60s → 90s (linear back-off at queue level).
     *
     * @return int[]
     */
    public function backoff(): array
    {
        return [30, 60, 90];
    }

    /**
     * Create a new SendSmsJob instance.
     *
     * @param  SmsData       $smsData     The SMS payload to send.
     * @param  string[]|null $recipients  Optional override recipients for bulk sends.
     *                                    When null, uses $smsData->mobile.
     */
    public function __construct(
        public readonly SmsData  $smsData,
        public readonly ?array   $recipients = null,
    ) {
        // Apply queue config defaults
        $this->onConnection(config('msg91.queue.connection'));
        $this->onQueue(config('msg91.queue.queue', 'default'));
    }

    /**
     * Execute the job.
     *
     * Delegates to Msg91::sendSms() or Msg91::sendBulkSms() depending
     * on whether $recipients was provided.
     */
    public function handle(): void
    {
        if ($this->recipients !== null && count($this->recipients) > 0) {
            Msg91::sendBulkSms($this->recipients, $this->smsData);
        } else {
            Msg91::sendSms($this->smsData);
        }
    }

    /**
     * Handle a job failure.
     *
     * Called by Laravel when all retry attempts ($tries) are exhausted.
     * Dispatches a MessageFailed event so listeners can react to the
     * permanent failure (e.g. alert on-call, log to DB, notify admin).
     *
     * @param  \Throwable  $exception  The exception that caused the final failure.
     */
    public function failed(\Throwable $exception): void
    {
        $recipientList = $this->recipients
            ?? $this->smsData->getRecipients();

        $recipientSummary = count($recipientList) > 3
            ? implode(',', array_slice($recipientList, 0, 3)) . '… +' . (count($recipientList) - 3) . ' more'
            : implode(',', $recipientList);

        event(new MessageFailed(
            channel:   'sms',
            payload:   [
                'recipients_count' => count($recipientList),
                'route'            => $this->smsData->route->value,
                'attempts'         => $this->attempts(),
                'job_class'        => static::class,
            ],
            exception: $exception,
            recipient: $recipientSummary,
        ));
    }

    /**
     * Get the display name for the queued job (visible in Horizon/Queue UI).
     */
    public function displayName(): string
    {
        $count = $this->recipients
            ? count($this->recipients)
            : count($this->smsData->getRecipients());

        return "SendSmsJob ({$count} recipient" . ($count > 1 ? 's' : '') . ')';
    }

    /**
     * Get the tags for the queued job (used by Horizon for filtering).
     *
     * @return string[]
     */
    public function tags(): array
    {
        return [
            'msg91',
            'sms',
            'route:' . $this->smsData->route->label(),
        ];
    }
}
