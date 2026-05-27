<?php

declare(strict_types=1);

namespace Parvion\Msg91\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Parvion\Msg91\DTOs\WhatsAppData;

/**
 * Class WhatsAppSent
 *
 * Fired when a WhatsApp message has been successfully accepted by the MSG91 API.
 */
class WhatsAppSent
{
    use Dispatchable;

    /**
     * @param  string|array<int, string>  $recipients
     */
    public function __construct(
        public readonly string|array $recipients,
        public readonly WhatsAppData $whatsAppData,
        public readonly array $response,
    ) {}
}
