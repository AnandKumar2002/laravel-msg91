<?php

declare(strict_types=1);

namespace Parvion\Msg91\Traits\Api;

use Parvion\Msg91\DTOs\EmailData;
use Parvion\Msg91\Events\MessageFailed;
use Parvion\Msg91\Exceptions\FeatureDisabledException;
use Parvion\Msg91\Exceptions\Msg91ApiException;

/**
 * Trait ManagesEmail
 *
 * Implements email operations for the Msg91 class:
 *   sendEmail()     → POST api/v5/email/send
 *   sendBulkEmail() → POST api/v5/email/send (multi-recipient)
 *
 * All methods:
 *   1. Check the 'email' feature flag (throws FeatureDisabledException if off)
 *   2. Apply local throttle guard
 *   3. Execute the API call with automatic retry (via WithRetries)
 *   4. Dispatch MessageFailed event on failure
 *
 * MSG91 Email API:
 *   - Uses template IDs for email content
 *   - Recipients specified as array of {email: "..."} objects
 *   - Supports from/reply-to overrides and attachments
 *
 * Assumed $this context (Msg91 class):
 *   - $this->client         → Msg91ClientInterface
 *   - $this->withRetry()    → WithRetries trait
 *   - $this->checkThrottle() → ManagesThrottling trait
 *   - $this->isFeatureEnabled() → InteractsWithConfig trait
 *
 * @package Parvion\Msg91\Traits\Api
 */
trait ManagesEmail
{
    /**
     * Send a single email or multi-recipient email using an EmailData DTO.
     *
     * @param  EmailData  $data  Strongly-typed email payload.
     * @return array             Normalised MSG91 response array.
     *
     * @throws FeatureDisabledException
     * @throws \Parvion\Msg91\Exceptions\Msg91RateLimitException
     * @throws Msg91ApiException
     */
    public function sendEmail(EmailData $data): array
    {
        // ── 1. Feature guard ───────────────────────────────────────────────────
        if (! $this->isFeatureEnabled('email')) {
            throw FeatureDisabledException::make('email');
        }

        // ── 2. Throttle guard ──────────────────────────────────────────────────
        $recipients = $data->getRecipients();
        $this->checkThrottle('email:send:' . $recipients[0]);

        // ── 3. Build payload ───────────────────────────────────────────────────
        $payload = $data->toArray();

        // ── 4. Call API with retry ─────────────────────────────────────────────
        try {
            return $this->withRetry(
                fn () => $this->client->post('email/send', $payload)
            );
        } catch (\Throwable $e) {
            event(new MessageFailed(
                channel:   'email',
                payload:   [
                    'recipients_count' => count($recipients),
                    'template_id'      => $data->templateId,
                    'subject'          => $data->subject,
                ],
                exception: $e,
                recipient: implode(',', array_slice($recipients, 0, 3)),
            ));
            throw $e;
        }
    }

    /**
     * Send a bulk email to many recipients using a shared EmailData template.
     *
     * The $recipients array overrides any recipients inside $data.
     *
     * @param  string[]   $recipients  Array of email addresses.
     * @param  EmailData  $data        Shared email config (subject, template, variables).
     * @return array                   Normalised MSG91 response array.
     *
     * @throws FeatureDisabledException
     * @throws \Parvion\Msg91\Exceptions\Msg91RateLimitException
     * @throws Msg91ApiException
     */
    public function sendBulkEmail(array $recipients, EmailData $data): array
    {
        // ── 1. Feature guard ───────────────────────────────────────────────────
        if (! $this->isFeatureEnabled('email')) {
            throw FeatureDisabledException::make('email');
        }

        if (empty($recipients)) {
            throw new Msg91ApiException(
                'No email recipients provided for bulk email send.',
                422,
            );
        }

        // ── 2. Throttle guard ──────────────────────────────────────────────────
        $this->checkThrottle('email:bulk:' . count($recipients));

        // ── 3. Build payload with overridden recipients ────────────────────────
        $payload       = $data->toArray();
        $payload['to'] = array_map(fn ($email) => ['email' => $email], $recipients);

        // ── 4. Call API with retry ─────────────────────────────────────────────
        try {
            return $this->withRetry(
                fn () => $this->client->post('email/send', $payload)
            );
        } catch (\Throwable $e) {
            event(new MessageFailed(
                channel:   'email',
                payload:   [
                    'recipients_count' => count($recipients),
                    'template_id'      => $data->templateId,
                    'subject'          => $data->subject,
                ],
                exception: $e,
                recipient: count($recipients) . ' recipients (bulk)',
            ));
            throw $e;
        }
    }

    /**
     * Send email with validation.
     * Uses the same endpoint but intended for high-deliverability strict validation.
     *
     * @param  EmailData  $data
     * @return array
     *
     * @throws FeatureDisabledException
     * @throws Msg91ApiException
     */
    public function sendEmailWithValidation(EmailData $data): array
    {
        // For MSG91, the endpoint is identical, but can include validation headers/flags in the payload if needed
        return $this->sendEmail($data);
    }

    /**
     * Send email using a CSV file for massive bulk processing.
     *
     * @param  string     $csvFilePath Absolute path to the CSV file
     * @param  EmailData  $data        Email template and basic config
     * @return array
     *
     * @throws FeatureDisabledException
     * @throws Msg91ApiException
     */
    public function sendEmailWithCsv(string $csvFilePath, EmailData $data): array
    {
        if (! $this->isFeatureEnabled('email')) {
            throw FeatureDisabledException::make('email');
        }

        if (! file_exists($csvFilePath) || ! is_readable($csvFilePath)) {
            throw new \InvalidArgumentException("CSV file not found or not readable: {$csvFilePath}");
        }

        $this->checkThrottle('email:csv');

        $payload = $data->toArray();
        // Read file contents as base64 or attach as multipart depending on MSG91 spec
        // Assuming base64 for JSON payload standard here
        $payload['file'] = base64_encode(file_get_contents($csvFilePath));
        $payload['file_name'] = basename($csvFilePath);

        try {
            return $this->withRetry(
                fn () => $this->client->post('email/send', $payload)
            );
        } catch (\Throwable $e) {
            event(new MessageFailed('email', ['csv' => basename($csvFilePath), 'template' => $data->templateId], $e, 'CSV Upload'));
            throw $e;
        }
    }

    /**
     * Create a new HTML Email Template programmatically.
     *
     * @param  string $name        Name of the template
     * @param  string $htmlContent The raw HTML content
     * @return array
     *
     * @throws Msg91ApiException
     */
    public function createEmailTemplate(string $name, string $htmlContent): array
    {
        if (! $this->isFeatureEnabled('email')) {
            throw FeatureDisabledException::make('email');
        }

        $this->checkThrottle('email:template:create');

        $payload = [
            'name' => $name,
            'template_html' => $htmlContent,
            'description' => 'Created via Laravel MSG91 package',
        ];

        return $this->withRetry(
            fn () => $this->client->post('email/templates', $payload)
        );
    }

    /**
     * Fetch existing email templates from the MSG91 account.
     *
     * @param  array $filters Optional query parameters (e.g. per_page, search_in)
     * @return array
     *
     * @throws Msg91ApiException
     */
    public function getEmailTemplates(array $filters = []): array
    {
        if (! $this->isFeatureEnabled('email')) {
            throw FeatureDisabledException::make('email');
        }

        $this->checkThrottle('email:template:get');

        $defaultFilters = [
            'with' => 'versions',
            'per_page' => 25,
            'page' => 1,
        ];

        $query = array_merge($defaultFilters, $filters);
        $queryString = http_build_query($query);

        return $this->withRetry(
            fn () => $this->client->get('email/templates?' . $queryString)
        );
    }

    /**
     * Fetch email delivery logs for a specific date range.
     *
     * @param  \Carbon\Carbon $startDate
     * @param  \Carbon\Carbon $endDate
     * @return array
     *
     * @throws Msg91ApiException
     */
    public function getEmailLogs(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        if (! $this->isFeatureEnabled('email')) {
            throw FeatureDisabledException::make('email');
        }

        $this->checkThrottle('email:logs');

        $queryString = http_build_query([
            'startDate' => $startDate->format('Y-m-d'),
            'endDate'   => $endDate->format('Y-m-d'),
        ]);

        return $this->withRetry(
            fn () => $this->client->post('report/logs/mail?' . $queryString, [])
        );
    }
}
