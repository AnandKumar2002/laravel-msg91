<?php

declare(strict_types=1);

namespace Parvion\Msg91\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeliveryStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $requestId;

    public string $status;

    public array $rawPayload;

    /**
     * Create a new event instance.
     */
    public function __construct(string $requestId, string $status, array $rawPayload)
    {
        $this->requestId = $requestId;
        $this->status = $status;
        $this->rawPayload = $rawPayload;
    }
}
