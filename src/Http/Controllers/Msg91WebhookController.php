<?php

declare(strict_types=1);

namespace Parvion\Msg91\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Parvion\Msg91\Events\MessageDeliveryStatusChanged;
use Parvion\Msg91\Events\Msg91WebhookReceived;

/**
 * Class Msg91WebhookController
 *
 * Handles MSG91 Delivery Receipt (DLR) Webhooks for SMS and OTP.
 * Dispatches generic events that developers can listen to in their own apps.
 *
 * Route Registration Example:
 * Route::post('/msg91/webhook', [\Parvion\Msg91\Http\Controllers\Msg91WebhookController::class, 'handle']);
 */
class Msg91WebhookController extends Controller
{
    /**
     * Handle incoming MSG91 webhook payload.
     * MSG91 sends JSON payloads containing delivery status for SMS, OTP, etc.
     *
     * @return JsonResponse
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        if (empty($payload)) {
            return response()->json(['status' => 'ignored', 'message' => 'Empty payload'], 200);
        }

        // Example dispatching:
        // We will dispatch a generic event containing the payload.
        // Developers can listen to this event to update their database logs.
        event(new Msg91WebhookReceived($payload));

        // Let's also parse typical DLR structures
        if (isset($payload['status']) && isset($payload['request_id'])) {
            $status = strtolower((string) $payload['status']);
            $requestId = $payload['request_id'];

            // Dispatch specific status events if recognizable
            if (in_array($status, ['delivered', 'failed', 'sent', 'dnd', 'invalid'])) {
                event(new MessageDeliveryStatusChanged($requestId, $status, $payload));
            }
        }

        return response()->json(['status' => 'success'], 200);
    }
}
