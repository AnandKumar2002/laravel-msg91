<?php

declare(strict_types=1);

namespace Parvion\Msg91\DTOs;

use InvalidArgumentException;

/**
 * Class WhatsAppData
 *
 * Immutable Data Transfer Object representing a WhatsApp message request via MSG91.
 * Supports plain text messages and pre-approved template messages.
 * Uses PHP 8.1 readonly constructor promotion for immutability.
 *
 * Usage (plain message):
 *   $wa = WhatsAppData::fromArray([
 *       'mobile'  => '919876543210',
 *       'message' => 'Hello! Your order is confirmed.',
 *   ]);
 *
 * Usage (template):
 *   $wa = WhatsAppData::fromArray([
 *       'mobile'      => '919876543210',
 *       'template_id' => 'your-wa-template-id',
 *       'variables'   => ['name' => 'Alice', 'order' => '#12345'],
 *   ]);
 *
 * @package Parvion\Msg91\DTOs
 */
class WhatsAppData
{
    public function __construct(
        /** Recipient mobile number in E.164 format (e.g. '919876543210'). */
        public readonly string  $mobile,

        /** Plain text message body. Required if $templateId is null. */
        public readonly ?string $message = null,

        /** MSG91 WhatsApp template ID. Required for template messages. */
        public readonly ?string $templateId = null,

        /**
         * Template variable substitutions (key → value pairs).
         * e.g. ['name' => 'Alice', 'order_id' => 'ORD-001']
         */
        public readonly array   $variables = [],

        /**
         * URL of a media file to send (image, video, document).
         * Required when sending a media message.
         */
        public readonly ?string $mediaUrl = null,

        /**
         * Media type: 'image', 'video', 'document', 'audio'.
         * Required when $mediaUrl is provided.
         */
        public readonly ?string $mediaType = null,

        /** Optional caption displayed below the media. */
        public readonly ?string $caption = null,
    ) {
        if (empty(trim($this->mobile))) {
            throw new InvalidArgumentException('WhatsAppData: mobile number must not be empty.');
        }

        if ($this->message === null && $this->templateId === null) {
            throw new InvalidArgumentException(
                'WhatsAppData: either message or template_id must be provided.'
            );
        }

        if ($this->mediaUrl !== null && $this->mediaType === null) {
            throw new InvalidArgumentException(
                'WhatsAppData: media_type is required when media_url is provided.'
            );
        }
    }

    /**
     * Construct a WhatsAppData from a plain array.
     *
     * Required keys: mobile + (message OR template_id)
     * Optional keys: variables, media_url, media_type, caption
     */
    public static function fromArray(array $data): self
    {
        return new self(
            mobile:     $data['mobile'] ?? '',
            message:    $data['message'] ?? null,
            templateId: $data['template_id'] ?? null,
            variables:  $data['variables'] ?? [],
            mediaUrl:   $data['media_url'] ?? null,
            mediaType:  $data['media_type'] ?? null,
            caption:    $data['caption'] ?? null,
        );
    }

    /**
     * Serialise to the MSG91 API request payload format.
     */
    public function toArray(): array
    {
        $payload = [
            'integrated_number' => $this->mobile,
        ];

        if ($this->templateId !== null) {
            // Template message
            $payload['template_id'] = $this->templateId;

            if (! empty($this->variables)) {
                $payload['shorturl'] = '0'; // Default
                $payload['realTimeResponse'] = '1';

                // MSG91 expects component variables as an array of objects
                $components = [];
                foreach ($this->variables as $key => $value) {
                    $components[] = ['type' => 'text', 'text' => (string) $value];
                }
                $payload['message']['payload']['components'] = $components;
            }
        } else {
            // Plain text message
            $payload['message']['payload']['text'] = $this->message;
            $payload['message']['type'] = 'text';
        }

        if ($this->mediaUrl !== null) {
            $payload['message']['payload'][$this->mediaType]['link'] = $this->mediaUrl;
            $payload['message']['type'] = $this->mediaType;

            if ($this->caption !== null) {
                $payload['message']['payload'][$this->mediaType]['caption'] = $this->caption;
            }
        }

        return $payload;
    }

    /**
     * Check if this is a template-based message.
     */
    public function isTemplate(): bool
    {
        return $this->templateId !== null;
    }

    /**
     * Check if this message contains media.
     */
    public function hasMedia(): bool
    {
        return $this->mediaUrl !== null;
    }
}
