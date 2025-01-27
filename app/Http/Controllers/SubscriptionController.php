<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    public function addFeature(Request $request)
    {
        $request->validate([
            'feature_key' => 'required|string|in:dynamic_qr_codes,monthly_scans,advanced_analytics,custom_branding',
            'value' => 'required',
            'price' => 'required|numeric|min:0',
        ]);

        try {
            $subscription = $request->user()->subscription;

            if (!$subscription) {
                $subscription = $this->subscriptionService->createSubscription($request->user());
            }

            $this->subscriptionService->addFeature(
                $subscription,
                $request->feature_key,
                $request->value,
                $request->price
            );

            return response()->json([
                'message' => 'Feature added successfully',
                'subscription' => $subscription->fresh()
            ]);
        } catch (Exception $e) {
            Log::error('Failed to add subscription feature: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancel(Request $request)
    {
        try {
            $subscription = $request->user()->subscription;

            if (!$subscription) {
                throw new Exception('No active subscription found');
            }

            $this->subscriptionService->cancelSubscription($subscription);

            return response()->json([
                'message' => 'Subscription cancelled successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Failed to cancel subscription: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function reactivate(Request $request)
    {
        try {
            $subscription = $request->user()->subscription()->withTrashed()->first();

            if (!$subscription) {
                throw new Exception('No subscription found');
            }

            $this->subscriptionService->reactivateSubscription($subscription);

            return response()->json([
                'message' => 'Subscription reactivated successfully',
                'subscription' => $subscription->fresh()
            ]);
        } catch (Exception $e) {
            Log::error('Failed to reactivate subscription: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
