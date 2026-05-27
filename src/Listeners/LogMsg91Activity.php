<?php

declare(strict_types=1);

namespace Parvion\Msg91\Listeners;

use Parvion\Msg91\Events\EmailSent;
use Parvion\Msg91\Events\MessageFailed;
use Parvion\Msg91\Events\OtpSent;
use Parvion\Msg91\Events\SmsSent;
use Parvion\Msg91\Events\WhatsAppSent;
use Parvion\Msg91\Logging\LogDriverManager;

/**
 * Class LogMsg91Activity
 *
 * Listens to success and failure events and writes a structured
 * log record via the LogDriverManager.
 */
class LogMsg91Activity
{
    public function __construct(
        private readonly LogDriverManager $logManager,
    ) {}

    /**
     * Handle the incoming event.
     */
    public function handle(OtpSent|SmsSent|EmailSent|WhatsAppSent|MessageFailed $event): void
    {
        match (true) {
            $event instanceof OtpSent => $this->handleOtpSent($event),
            $event instanceof SmsSent => $this->handleSmsSent($event),
            $event instanceof EmailSent => $this->handleEmailSent($event),
            $event instanceof WhatsAppSent => $this->handleWhatsAppSent($event),
            $event instanceof MessageFailed => $this->handleMessageFailed($event),
        };
    }

    private function handleOtpSent(OtpSent $event): void
    {
        if (config('msg91.logging.channels.otp', true) === false) {
            return;
        }
        $this->logManager->logSuccess(
            channel: 'otp',
            action: 'send_otp',
            recipient: $event->formattedMobile,
            request: [
                'template_id' => $event->otpData->templateId,
                'otp_length' => $event->otpData->otpLength,
                'otp_expiry' => $event->otpData->otpExpiry,
            ],
            response: $event->response,
            httpStatus: (int) ($event->response['http_status'] ?? 200),
            durationMs: 0,
        );
    }

    private function handleSmsSent(SmsSent $event): void
    {
        if (config('msg91.logging.channels.sms', true) === false) {
            return;
        }

        $recipientList = is_array($event->recipients) ? $event->recipients : [$event->recipients];
        $recipientSummary = count($recipientList) > 3
            ? implode(',', array_slice($recipientList, 0, 3)).'… +'.(count($recipientList) - 3).' more'
            : implode(',', $recipientList);

        $this->logManager->logSuccess(
            channel: 'sms',
            action: 'send_sms',
            recipient: $recipientSummary,
            request: [
                'route' => $event->smsData->route->value,
                'recipients_count' => count($recipientList),
                'template_id' => $event->smsData->message,
            ],
            response: $event->response,
            httpStatus: (int) ($event->response['http_status'] ?? 200),
            durationMs: 0,
        );
    }

    private function handleEmailSent(EmailSent $event): void
    {
        if (config('msg91.logging.channels.email', true) === false) {
            return;
        }

        $recipientList = is_array($event->recipients) ? $event->recipients : [$event->recipients];
        $recipientSummary = count($recipientList) > 3
            ? implode(',', array_slice($recipientList, 0, 3)).'… +'.(count($recipientList) - 3).' more'
            : implode(',', $recipientList);

        $this->logManager->logSuccess(
            channel: 'email',
            action: 'send_email',
            recipient: $recipientSummary,
            request: [
                'subject' => $event->emailData->subject,
                'template_id' => $event->emailData->templateId,
                'recipients_count' => count($recipientList),
            ],
            response: $event->response,
            httpStatus: (int) ($event->response['http_status'] ?? 200),
            durationMs: 0,
        );
    }

    private function handleWhatsAppSent(WhatsAppSent $event): void
    {
        if (config('msg91.logging.channels.whatsapp', true) === false) {
            return;
        }

        $recipientList = is_array($event->recipients) ? $event->recipients : [$event->recipients];
        $recipientSummary = count($recipientList) > 3
            ? implode(',', array_slice($recipientList, 0, 3)).'… +'.(count($recipientList) - 3).' more'
            : implode(',', $recipientList);

        $this->logManager->logSuccess(
            channel: 'whatsapp',
            action: 'send_whatsapp',
            recipient: $recipientSummary,
            request: [
                'is_template' => $event->whatsAppData->isTemplate(),
                'has_media' => $event->whatsAppData->hasMedia(),
                'recipients_count' => count($recipientList),
            ],
            response: $event->response,
            httpStatus: (int) ($event->response['http_status'] ?? 200),
            durationMs: 0,
        );
    }

    private function handleMessageFailed(MessageFailed $event): void
    {
        if (config("msg91.logging.channels.{$event->channel}", true) === false) {
            return;
        }
        $this->logManager->logFailure(
            channel: $event->channel,
            action: 'api_call_failed',
            recipient: $event->recipient,
            request: $event->payload,
            exception: $event->exception,
            httpStatus: method_exists($event->exception, 'getStatusCode')
                ? $event->exception->getStatusCode()
                : null,
            durationMs: 0,
        );
    }
}
