<?php

declare(strict_types=1);

namespace Parvion\Msg91\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeliveryStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var string
     */
    public string $requestId;

    /**
     * @var string
     */
    public string $status;

    /**
     * @var array
     */
    public array $rawPayload;

    /**
     * Create a new event instance.
     *
     * @param string $requestId
     * @param string $status
     * @param array $rawPayload
     */
    public function __construct(string $requestId, string $status, array $rawPayload)
    {
        $this->requestId = $requestId;
        $this->status = $status;
        $this->rawPayload = $rawPayload;
    }
}
