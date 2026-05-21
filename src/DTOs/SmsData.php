<?php

declare(strict_types=1);

namespace Parvion\Msg91\DTOs;

use InvalidArgumentException;
use Parvion\Msg91\Enums\SmsRoute;

/**
 * Class SmsData
 *
 * Immutable Data Transfer Object representing a single or bulk SMS request.
 * Uses PHP 8.1 readonly constructor promotion for immutability.
 *
 * Usage (single):
 *   $sms = SmsData::fromArray([
 *       'mobile'  => '919876543210',
 *       'message' => 'Your order has been shipped.',
 *   ]);
 *
 * Usage (bulk):
 *   $sms = SmsData::fromArray([
 *       'mobile'  => ['919876543210', '918765432109'],
 *       'message' => 'Your order #{{order_id}} has been shipped.',
 *       'variables' => [['order_id' => 'ORD-001'], ['order_id' => 'ORD-002']],
 *   ]);
 *
 * @package Parvion\Msg91\DTOs
 */
class SmsData
{
    public function __construct(
        /**
         * Recipient mobile number(s) in E.164 format.
         * A string for single sends, an array for bulk sends.
         */
        public readonly string|array $mobile,

        /** The SMS message body. Use {{variable}} placeholders for templates. */
        public readonly string       $message,

        /** MSG91 routing type. Default: Transactional (route 4). */
        public readonly SmsRoute     $route = SmsRoute::Transactional,

        /**
         * Sender ID. Defaults to config('msg91.sender_id') if null.
         * Must be exactly 6 alphanumeric characters.
         */
        public readonly ?string      $senderId = null,

        /** Send as Unicode (required for non-Latin scripts like Hindi, Arabic). */
        public readonly bool         $unicode = false,

        /** Send as a flash message (displays immediately without saving). */
        public readonly bool         $flash = false,

        /**
         * Per-recipient template variable sets.
         * For bulk sends, each index maps to the corresponding $mobile entry.
         * e.g. [['name' => 'Alice'], ['name' => 'Bob']]
         */
        public readonly array        $variables = [],
    ) {
        $recipients = is_array($this->mobile) ? $this->mobile : [$this->mobile];

        if (empty($recipients) || in_array('', $recipients, true)) {
            throw new InvalidArgumentException('SmsData: mobile number(s) must not be empty.');
        }

        if (empty(trim($this->message))) {
            throw new InvalidArgumentException('SmsData: message must not be empty.');
        }
    }

    /**
     * Construct a SmsData from a plain array.
     *
     * Required keys: mobile, message
     * Optional keys: route (SmsRoute or int), sender_id, unicode, flash, variables
     */
    public static function fromArray(array $data): self
    {
        $route = $data['route'] ?? null;

        if ($route instanceof SmsRoute) {
            $smsRoute = $route;
        } elseif (is_int($route)) {
            $smsRoute = SmsRoute::from($route);
        } else {
            $smsRoute = SmsRoute::from((int) config('msg91.sms.route', 4));
        }

        return new self(
            mobile:    $data['mobile'] ?? '',
            message:   $data['message'] ?? '',
            route:     $smsRoute,
            senderId:  $data['sender_id'] ?? null,
            unicode:   (bool) ($data['unicode'] ?? config('msg91.sms.unicode', false)),
            flash:     (bool) ($data['flash'] ?? config('msg91.sms.flash', false)),
            variables: $data['variables'] ?? [],
        );
    }

    /**
     * Serialise to the MSG91 API request payload format.
     */
    public function toArray(): array
    {
        $recipients = is_array($this->mobile) ? $this->mobile : [$this->mobile];
        $senderId   = $this->senderId ?? config('msg91.sender_id', '');

        $payload = [
            'sender'    => $senderId,
            'route'     => $this->route->value,
            'country'   => config('msg91.default_country_code', '91'),
            'unicode'   => $this->unicode ? '1' : '0',
            'flash'     => $this->flash ? '1' : '0',
            'sms'       => [],
        ];

        foreach ($recipients as $index => $number) {
            $sms = [
                'message' => $this->buildMessage($index),
                'to'      => [$number],
            ];
            $payload['sms'][] = $sms;
        }

        return $payload;
    }

    /**
     * Interpolate template variables for a given recipient index.
     */
    private function buildMessage(int $index): string
    {
        if (empty($this->variables)) {
            return $this->message;
        }

        $vars    = $this->variables[$index] ?? $this->variables[0] ?? [];
        $message = $this->message;

        foreach ($vars as $key => $value) {
            $message = str_replace('{{' . $key . '}}', (string) $value, $message);
        }

        return $message;
    }

    /**
     * Check if this is a bulk (multi-recipient) send.
     */
    public function isBulk(): bool
    {
        return is_array($this->mobile) && count($this->mobile) > 1;
    }

    /**
     * Return recipients as a guaranteed array.
     *
     * @return string[]
     */
    public function getRecipients(): array
    {
        return is_array($this->mobile) ? $this->mobile : [$this->mobile];
    }
}
