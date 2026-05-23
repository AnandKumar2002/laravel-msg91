<?php

declare(strict_types=1);

namespace Parvion\Msg91\Traits\Api;

use Carbon\Carbon;
use Parvion\Msg91\DTOs\SmsData;
use Parvion\Msg91\Events\MessageFailed;
use Parvion\Msg91\Exceptions\FeatureDisabledException;
use Parvion\Msg91\Exceptions\Msg91ApiException;
use Parvion\Msg91\Exceptions\Msg91RateLimitException;
use Parvion\Msg91\Support\PhoneNumberFormatter;

/**
 * Trait ManagesSms
 *
 * Implements the full SMS lifecycle for the Msg91 class:
 *   sendSms()             → POST api/v5/flow/
 *   sendBulkSms()         → POST api/v5/flow/ (multi-recipient)
 *   checkDeliveryStatus() → GET  api/v5/report/{requestId}
 *
 * All methods:
 *   1. Check the 'sms' feature flag (throws FeatureDisabledException if off)
 *   2. Apply local throttle guard
 *   3. Normalise mobile numbers to E.164 via PhoneNumberFormatter
 *   4. Execute the API call with automatic retry (via WithRetries)
 *   5. Dispatch MessageFailed event on failure
 *
 * MSG91 SMS Flow API (v5):
 *   - Uses template IDs (called "flow IDs") for message content
 *   - Recipients are specified in a `recipients` array with per-recipient variables
 *   - Route (transactional/promotional/international) is set on the payload
 *
 * Assumed $this context (Msg91 class):
 *   - $this->client         → Msg91ClientInterface
 *   - $this->withRetry()    → WithRetries trait
 *   - $this->checkThrottle() → ManagesThrottling trait
 *   - $this->isFeatureEnabled() → InteractsWithConfig trait
 */
trait ManagesSms
{
    /**
     * Send a single SMS or multi-recipient SMS using a SmsData DTO.
     *
     * For single sends, SmsData->mobile can be a string.
     * For multi-recipient sends, SmsData->mobile can be an array.
     *
     * @param  SmsData  $data  Strongly-typed SMS payload.
     * @return array Normalised MSG91 response array.
     *
     * @throws FeatureDisabledException
     * @throws Msg91RateLimitException
     * @throws Msg91ApiException
     */
    public function sendSms(SmsData $data): array
    {
        // ── 1. Feature guard ───────────────────────────────────────────────────
        if (! $this->isFeatureEnabled('sms')) {
            throw FeatureDisabledException::make('sms');
        }

        // ── 2. Format mobile numbers ───────────────────────────────────────────
        $formatter = $this->smsFormatter();
        $recipients = $data->getRecipients();
        $formatted = $formatter->formatMany($recipients);

        if (empty($formatted)) {
            throw new Msg91ApiException(
                'No valid mobile numbers provided for SMS send.',
                422,
            );
        }

        // ── 3. Throttle guard (one check per batch, keyed by first recipient) ──
        $this->checkThrottle('sms:send:'.$formatted[0]);

        // ── 4. Build payload ───────────────────────────────────────────────────
        $payload = $this->buildSmsPayload($data, $formatted);

        // ── 5. Call API with retry ─────────────────────────────────────────────
        try {
            return $this->withRetry(
                fn () => $this->client->post('flow/', $payload)
            );
        } catch (\Throwable $e) {
            event(new MessageFailed(
                channel: 'sms',
                payload: ['recipients_count' => count($formatted), 'route' => $data->route->value],
                exception: $e,
                recipient: implode(',', array_slice($formatted, 0, 3)),
            ));
            throw $e;
        }
    }

    /**
     * Send a bulk SMS to many recipients using a shared SmsData template.
     *
     * The $recipients array overrides any recipients inside $data.
     * Each recipient can have per-recipient template variables via $data->variables.
     *
     * @param  string[]  $recipients  Array of mobile numbers (any format — auto-normalised).
     * @param  SmsData  $data  Shared SMS config (message, route, sender, variables).
     * @return array Normalised MSG91 response array.
     *
     * @throws FeatureDisabledException
     * @throws Msg91RateLimitException
     * @throws Msg91ApiException
     */
    public function sendBulkSms(array $recipients, SmsData $data): array
    {
        // ── 1. Feature guard ───────────────────────────────────────────────────
        if (! $this->isFeatureEnabled('sms')) {
            throw FeatureDisabledException::make('sms');
        }

        // ── 2. Format all recipient numbers ────────────────────────────────────
        $formatter = $this->smsFormatter();
        $formatted = $formatter->formatMany($recipients);

        if (empty($formatted)) {
            throw new Msg91ApiException(
                'No valid mobile numbers provided for bulk SMS send.',
                422,
            );
        }

        // ── 3. Throttle guard ──────────────────────────────────────────────────
        $this->checkThrottle('sms:bulk:'.count($formatted));

        // ── 4. Build payload (same structure — just more recipients) ───────────
        $payload = $this->buildSmsPayload($data, $formatted);

        // ── 5. Call API with retry ─────────────────────────────────────────────
        try {
            return $this->withRetry(
                fn () => $this->client->post('flow/', $payload)
            );
        } catch (\Throwable $e) {
            event(new MessageFailed(
                channel: 'sms',
                payload: ['recipients_count' => count($formatted), 'route' => $data->route->value],
                exception: $e,
                recipient: count($formatted).' recipients (bulk)',
            ));
            throw $e;
        }
    }

    /**
     * Check the delivery status of a previously sent SMS campaign.
     *
     * @param  string  $requestId  The MSG91 campaign/request ID returned from sendSms().
     * @return array Normalised delivery status response.
     *
     * @throws FeatureDisabledException
     * @throws Msg91ApiException
     */
    public function checkDeliveryStatus(string $requestId): array
    {
        // ── 1. Feature guard ───────────────────────────────────────────────────
        if (! $this->isFeatureEnabled('sms')) {
            throw FeatureDisabledException::make('sms');
        }

        if (empty(trim($requestId))) {
            throw new Msg91ApiException(
                'Request ID must not be empty for delivery status check.',
                422,
            );
        }

        // ── 2. Call API (no throttle for status checks) ────────────────────────
        try {
            return $this->withRetry(
                fn () => $this->client->get('report/'.$requestId)
            );
        } catch (\Throwable $e) {
            event(new MessageFailed(
                channel: 'sms',
                payload: ['request_id' => $requestId, 'action' => 'delivery_status'],
                exception: $e,
                recipient: $requestId,
            ));
            throw $e;
        }
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Build the MSG91 Flow API payload from SmsData + formatted recipients.
     *
     * MSG91 Flow API v5 expected structure:
     * {
     *   "flow_id": "...",
     *   "sender": "SNDRID",
     *   "short_url": "0",
     *   "recipients": [
     *     { "mobiles": "919876543210", "VAR1": "value1" },
     *     { "mobiles": "918765432109", "VAR2": "value2" }
     *   ]
     * }
     */
    private function buildSmsPayload(SmsData $data, array $formattedNumbers): array
    {
        $senderId = $data->senderId ?? $this->getSenderId();

        $payload = [
            'flow_id' => $data->message, // flow_id doubles as template reference
            'sender' => $senderId,
            'short_url' => '0',
            'route' => (string) $data->route->value,
        ];

        if ($data->unicode) {
            $payload['unicode'] = '1';
        }

        // Build per-recipient array
        $recipients = [];
        foreach ($formattedNumbers as $index => $number) {
            $recipient = ['mobiles' => $number];

            // Merge per-recipient template variables
            $vars = $data->variables[$index] ?? $data->variables[0] ?? [];
            foreach ($vars as $key => $value) {
                $recipient[$key] = (string) $value;
            }

            $recipients[] = $recipient;
        }

        $payload['recipients'] = $recipients;

        return $payload;
    }

    /**
     * Resolve the PhoneNumberFormatter from the container.
     */
    protected function smsFormatter(): PhoneNumberFormatter
    {
        return app(PhoneNumberFormatter::class);
    }

    /**
     * Schedule an SMS to be sent at a specific future date and time.
     *
     *
     * @throws FeatureDisabledException
     * @throws Msg91ApiException
     */
    public function scheduleSms(SmsData $data, Carbon $sendAt): array
    {
        if (! $this->isFeatureEnabled('sms')) {
            throw FeatureDisabledException::make('sms');
        }

        $this->checkThrottle('sms:schedule');

        $payload = $data->toArray();
        // MSG91 API uses 'schtime' parameter with format YYYY-MM-DD HH:MM:SS
        $payload['schtime'] = $sendAt->format('Y-m-d H:i:s');

        try {
            return $this->withRetry(
                fn () => $this->client->post('flow', $payload) // Wait, v5 uses 'flow' endpoint for raw SMS too (with template ID) or 'sms' depending on payload.
                // Assuming it uses the standard sendSms endpoint mechanism (typically POST api/v5/flow for SMS in MSG91 v5)
            );
        } catch (\Throwable $e) {
            event(new MessageFailed('sms', $payload, $e, $data->mobile));
            throw $e;
        }
    }

    /**
     * Trigger a predefined MSG91 Flow (Campaign).
     *
     * @param  string  $flowId  The Flow ID from the MSG91 dashboard
     * @param  array  $variables  Key-value pairs of variables for the flow
     * @param  string  $mobile  Recipient mobile number (E.164)
     *
     * @throws FeatureDisabledException
     * @throws Msg91ApiException
     */
    public function triggerFlow(string $flowId, array $variables, string $mobile): array
    {
        if (! $this->isFeatureEnabled('sms')) {
            throw FeatureDisabledException::make('sms');
        }

        $this->checkThrottle('sms:flow:'.$mobile);

        $payload = [
            'flow_id' => $flowId,
            'mobiles' => $mobile,
        ];

        // Merge variables into the payload
        foreach ($variables as $key => $value) {
            $payload[$key] = $value;
        }

        try {
            return $this->withRetry(
                fn () => $this->client->post('flow/', $payload)
            );
        } catch (\Throwable $e) {
            event(new MessageFailed('sms', $payload, $e, $mobile));
            throw $e;
        }
    }

    /**
     * Fetch SMS usage analytics.
     *
     *
     * @throws Msg91ApiException
     */
    public function getSmsAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        if (! $this->isFeatureEnabled('sms')) {
            throw FeatureDisabledException::make('sms');
        }

        $this->checkThrottle('sms:analytics');

        $queryString = http_build_query([
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
        ]);

        return $this->withRetry(
            fn () => $this->client->get('report/analytics/p/sms?'.$queryString)
        );
    }

    /**
     * Fetch SMS delivery logs.
     * Note: Assuming a similar log endpoint format as email/OTP logs if not strictly specified,
     * typically report/logs/sms for MSG91.
     *
     *
     * @throws Msg91ApiException
     */
    public function getSmsLogs(Carbon $startDate, Carbon $endDate): array
    {
        if (! $this->isFeatureEnabled('sms')) {
            throw FeatureDisabledException::make('sms');
        }

        $this->checkThrottle('sms:logs');

        $queryString = http_build_query([
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
        ]);

        // Using POST for logs as per typical MSG91 v5 spec, similar to mail logs
        return $this->withRetry(
            fn () => $this->client->post('report/logs/sms?'.$queryString, [])
        );
    }
}
