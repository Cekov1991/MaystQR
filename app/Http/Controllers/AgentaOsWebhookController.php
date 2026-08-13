<?php

namespace App\Http\Controllers;

use App\Jobs\GrantSubscriptionEntitlement;
use App\Services\AgentaOS\WebhookSignature;
use App\Services\BillingAlerts;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AgentaOsWebhookController extends Controller
{
    /**
     * Verify, queue, return. AgentaOS allows 10 seconds and retries three
     * times, so nothing slow may happen inside this request.
     */
    public function __invoke(Request $request): Response
    {
        // The signature covers the raw bytes. Re-encoding the parsed array
        // would produce a different string and fail every time.
        $payload = $request->getContent();

        $isValid = WebhookSignature::isValid(
            $payload,
            $request->header('X-AgentaOS-Signature'),
            config('services.agentaos.webhook_secret'),
        );

        if (! $isValid) {
            // Usually a probe, but a rotated secret looks exactly the same from
            // here — and in that case every real payment is being dropped.
            BillingAlerts::raise(
                'webhook-signature-rejected',
                'An AgentaOS webhook was rejected for an invalid signature. If AGENTAOS_WEBHOOK_SECRET is stale, every payment is being dropped.',
                ['ip' => $request->ip()],
            );

            return response('Invalid signature', 400);
        }

        $event = json_decode($payload, true);

        if (! is_array($event)) {
            return response('Malformed payload', 400);
        }

        // Only checkout completion is actionable. AgentaOS's other two events
        // (send.completed, send.failed) concern outbound transfers, which this
        // application never initiates.
        if (($event['type'] ?? null) === 'checkout.session.completed') {
            GrantSubscriptionEntitlement::dispatch($event['data'] ?? []);
        }

        return response('', 204);
    }
}
