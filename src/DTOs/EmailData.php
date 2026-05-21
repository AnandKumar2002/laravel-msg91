<?php

declare(strict_types=1);

namespace Parvion\Msg91\DTOs;

use InvalidArgumentException;

/**
 * Class EmailData
 *
 * Immutable Data Transfer Object representing an email send request via MSG91.
 * Uses PHP 8.1 readonly constructor promotion for immutability.
 *
 * Usage:
 *   $email = EmailData::fromArray([
 *       'to'          => 'user@example.com',
 *       'subject'     => 'Welcome to Our Platform',
 *       'template_id' => 'your-msg91-email-template-id',
 *       'variables'   => ['name' => 'Alice', 'link' => 'https://...'],
 *   ]);
 *
 *   Msg91::sendEmail($email);
 *
 * @package Parvion\Msg91\DTOs
 */
class EmailData
{
    public function __construct(
        /**
         * Recipient email address(es).
         * A string for single sends, an array of emails for bulk sends.
         */
        public readonly string|array $to,

        /** Email subject line. */
        public readonly string       $subject,

        /** MSG91 email template ID. */
        public readonly string       $templateId,

        /**
         * Template variable substitutions (key → value pairs).
         * e.g. ['name' => 'Alice', 'order_id' => '12345']
         */
        public readonly array        $variables = [],

        /**
         * From email address. Falls back to MSG91 account default if null.
         */
        public readonly ?string      $from = null,

        /**
         * Display name for the From address.
         */
        public readonly ?string      $fromName = null,

        /**
         * Reply-To email address. Optional.
         */
        public readonly ?string      $replyTo = null,

        /**
         * Attachments as an array of absolute file paths or URLs.
         * MSG91 accepts publicly accessible URLs for attachments.
         */
        public readonly array        $attachments = [],
    ) {
        $recipients = is_array($this->to) ? $this->to : [$this->to];

        if (empty($recipients) || in_array('', $recipients, true)) {
            throw new InvalidArgumentException('EmailData: recipient email(s) must not be empty.');
        }

        if (empty(trim($this->subject))) {
            throw new InvalidArgumentException('EmailData: subject must not be empty.');
        }

        if (empty(trim($this->templateId))) {
            throw new InvalidArgumentException('EmailData: template_id must not be empty.');
        }
    }

    /**
     * Construct an EmailData from a plain array.
     *
     * Required keys: to, subject, template_id
     * Optional keys: variables, from, from_name, reply_to, attachments
     */
    public static function fromArray(array $data): self
    {
        return new self(
            to:          $data['to'] ?? '',
            subject:     $data['subject'] ?? '',
            templateId:  $data['template_id'] ?? '',
            variables:   $data['variables'] ?? [],
            from:        $data['from'] ?? null,
            fromName:    $data['from_name'] ?? null,
            replyTo:     $data['reply_to'] ?? null,
            attachments: $data['attachments'] ?? [],
        );
    }

    /**
     * Serialise to the MSG91 API request payload format.
     */
    public function toArray(): array
    {
        $recipients = is_array($this->to) ? $this->to : [$this->to];

        $payload = [
            'to'          => array_map(fn ($email) => ['email' => $email], $recipients),
            'subject'     => $this->subject,
            'template_id' => $this->templateId,
        ];

        if (! empty($this->variables)) {
            $payload['variables'] = $this->variables;
        }

        if ($this->from !== null) {
            $payload['from'] = ['email' => $this->from];

            if ($this->fromName !== null) {
                $payload['from']['name'] = $this->fromName;
            }
        }

        if ($this->replyTo !== null) {
            $payload['reply_to'] = [['email' => $this->replyTo]];
        }

        if (! empty($this->attachments)) {
            $payload['attachments'] = $this->attachments;
        }

        return $payload;
    }

    /**
     * Return recipients as a guaranteed array.
     *
     * @return string[]
     */
    public function getRecipients(): array
    {
        return is_array($this->to) ? $this->to : [$this->to];
    }

    /**
     * Check if this is a bulk (multi-recipient) send.
     */
    public function isBulk(): bool
    {
        return is_array($this->to) && count($this->to) > 1;
    }
}
