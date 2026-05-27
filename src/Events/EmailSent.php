<?php

declare(strict_types=1);

namespace Parvion\Msg91\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Parvion\Msg91\DTOs\EmailData;

/**
 * Class EmailSent
 *
 * Fired when an Email (single or bulk) has been successfully accepted by the MSG91 API.
 */
class EmailSent
{
    use Dispatchable;

    /**
     * @param  string|array<int, string>  $recipients
     */
    public function __construct(
        public readonly string|array $recipients,
        public readonly EmailData $emailData,
        public readonly array $response,
    ) {}
}
