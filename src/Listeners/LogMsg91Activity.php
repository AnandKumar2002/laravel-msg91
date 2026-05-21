<?php

declare(strict_types=1);

namespace Parvion\Msg91\Listeners;

use Parvion\Msg91\Events\MessageFailed;
use Parvion\Msg91\Events\OtpSent;
use Parvion\Msg91\Logging\LogDriverManager;

/**
 * Class LogMsg91Activity
 *
 * Listens to OtpSent and MessageFailed events and writes a structured
 * log record via the LogDriverManager (which delegates to the configured
 * log driver: null, log, database, or stack).
 *
 * Registered conditionally in Msg91ServiceProvider::boot() — only when
 * config('msg91.logging.driver') !== 'null'.
 *
 * This listener handles both event types via a single handle() method
 * using PHP's union types + match expression.
 *
 * @package Parvion\Msg91\Listeners
 */
class LogMsg91Activity
{
    public function __construct(
        private readonly LogDriverManager $logManager,
    ) {}

    /**
     * Handle the incoming event.
     *
     * Supports both OtpSent and MessageFailed events.
     * Additional event types can be added here as the package grows.
     */
    public function handle(OtpSent|MessageFailed $event): void
    {
        match (true) {
            $event instanceof OtpSent      => $this->handleOtpSent($event),
            $event instanceof MessageFailed => $this->handleMessageFailed($event),
        };
    }

    /**
     * Log a successful OTP send.
     */
    private function handleOtpSent(OtpSent $event): void
    {
        $this->logManager->logSuccess(
            channel:    'otp',
            action:     'send_otp',
            recipient:  $event->formattedMobile,
            request:    [
                'template_id' => $event->otpData->templateId,
                'otp_length'  => $event->otpData->otpLength,
                'otp_expiry'  => $event->otpData->otpExpiry,
            ],
            response:   $event->response,
            httpStatus: (int) ($event->response['http_status'] ?? 200),
            durationMs: 0, // Duration not available from events
        );
    }

    /**
     * Log a failed MSG91 API call.
     */
    private function handleMessageFailed(MessageFailed $event): void
    {
        $this->logManager->logFailure(
            channel:    $event->channel,
            action:     'api_call_failed',
            recipient:  $event->recipient,
            request:    $event->payload,
            exception:  $event->exception,
            httpStatus: method_exists($event->exception, 'getStatusCode')
                ? $event->exception->getStatusCode()
                : null,
            durationMs: 0, // Duration not available from events
        );
    }
}
