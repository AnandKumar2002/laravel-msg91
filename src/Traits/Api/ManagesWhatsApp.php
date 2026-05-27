<?php

declare(strict_types=1);

namespace Parvion\Msg91\Traits\Api;

use Parvion\Msg91\DTOs\WhatsAppData;
use Parvion\Msg91\Events\MessageFailed;
use Parvion\Msg91\Events\WhatsAppSent;
use Parvion\Msg91\Exceptions\FeatureDisabledException;
use Parvion\Msg91\Exceptions\Msg91ApiException;
use Parvion\Msg91\Exceptions\Msg91RateLimitException;
use Parvion\Msg91\Support\PhoneNumberFormatter;

/**
 * Trait ManagesWhatsApp
 *
 * Implements WhatsApp messaging operations for the Msg91 class:
 *   sendWhatsApp()         → POST api/v5/whatsapp/whatsapp-outbound-message/
 *   sendWhatsAppTemplate() → POST api/v5/whatsapp/whatsapp-outbound-message/ (template-based)
 *
 * MSG91 WhatsApp API:
 *   - Supports plain text, template, and media messages
 *   - Uses `integrated_number` as the recipient field
 *   - Template messages require pre-approved template IDs
 *
 * All methods:
 *   1. Check the 'whatsapp' feature flag
 *   2. Apply local throttle guard
 *   3. Normalise mobile number to E.164
 *   4. Execute with retry
 *   5. Dispatch MessageFailed on failure
 */
trait ManagesWhatsApp
{
    /**
     * Send a WhatsApp message (plain text or media).
     *
     * For template messages, prefer sendWhatsAppTemplate() which enforces
     * that a template_id is set.
     *
     * @param  WhatsAppData  $data  Strongly-typed WhatsApp payload.
     * @return array Normalised MSG91 response array.
     *
     * @throws FeatureDisabledException
     * @throws Msg91RateLimitException
     * @throws Msg91ApiException
     */
    public function sendWhatsApp(WhatsAppData $data): array
    {
        // ── 1. Feature guard ───────────────────────────────────────────────────
        if (! $this->isFeatureEnabled('whatsapp')) {
            throw FeatureDisabledException::make('whatsapp');
        }

        // ── 2. Format mobile number ────────────────────────────────────────────
        $formattedMobile = $this->whatsAppFormatter()->format(
            $data->mobile,
            $this->getDefaultCountryCode()
        );

        // ── 3. Throttle guard ──────────────────────────────────────────────────
        $this->checkThrottle('whatsapp:send:'.$formattedMobile);

        // ── 4. Build payload with formatted mobile ─────────────────────────────
        $payload = $data->toArray();
        $payload['integrated_number'] = $formattedMobile;

        // ── 5. Call API with retry ─────────────────────────────────────────────
        try {
            $response = $this->withRetry(
                fn () => $this->client->post(
                    'whatsapp/whatsapp-outbound-message/',
                    $payload
                )
            );

            event(new WhatsAppSent($formattedMobile, $data, $response));

            return $response;
        } catch (\Throwable $e) {
            event(new MessageFailed(
                channel: 'whatsapp',
                payload: [
                    'mobile' => $formattedMobile,
                    'is_template' => $data->isTemplate(),
                    'has_media' => $data->hasMedia(),
                ],
                exception: $e,
                recipient: $formattedMobile,
            ));
            throw $e;
        }
    }

    /**
     * Send a WhatsApp template message.
     *
     * This is a convenience wrapper around sendWhatsApp() that validates
     * the WhatsAppData has a template_id set. MSG91 requires template
     * messages to use pre-approved templates registered on the dashboard.
     *
     * @param  WhatsAppData  $data  Must have templateId set.
     * @return array Normalised MSG91 response array.
     *
     * @throws \InvalidArgumentException If templateId is null.
     * @throws FeatureDisabledException
     * @throws Msg91RateLimitException
     * @throws Msg91ApiException
     */
    public function sendWhatsAppTemplate(WhatsAppData $data): array
    {
        if (! $data->isTemplate()) {
            throw new \InvalidArgumentException(
                'sendWhatsAppTemplate() requires a WhatsAppData with template_id set. '.
                'For plain text messages, use sendWhatsApp() instead.'
            );
        }

        return $this->sendWhatsApp($data);
    }

    /**
     * Resolve the PhoneNumberFormatter from the container.
     */
    protected function whatsAppFormatter(): PhoneNumberFormatter
    {
        return app(PhoneNumberFormatter::class);
    }
}
