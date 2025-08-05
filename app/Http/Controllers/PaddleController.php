<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use App\Models\QrCodePackage;
use App\Models\QrCodePackagePurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaddleController extends Controller
{
    /**
     * Handle Paddle webhooks
     */
    public function webhook(Request $request)
    {
        $signature = $request->header('Paddle-Signature');
        $payload = $request->getContent();

        Log::info('Paddle webhook received', [
            'signature_present' => !empty($signature),
            'payload_size' => strlen($payload)
        ]);


        try {
            $data = json_decode($payload, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Invalid JSON in Paddle webhook', [
                    'json_error' => json_last_error_msg()
                ]);
                return response('Invalid JSON', 400);
            }

            $eventType = $data['event_type'] ?? null;
            $eventData = $data['data'] ?? [];

            Log::info('Paddle webhook processed', [
                'event_type' => $eventType,
                'transaction_id' => $eventData['id'] ?? 'unknown'
            ]);

            // Handle different event types
            switch ($eventType) {
                case 'transaction.completed':
                    $this->handleTransactionCompleted($eventData);
                    break;

                case 'transaction.payment_failed':
                    $this->handleTransactionFailed($eventData);
                    break;

                case 'transaction.created':
                    $this->handleTransactionCreated($eventData);
                    break;

                default:
                    Log::info('Unhandled Paddle webhook event type', [
                        'event_type' => $eventType
                    ]);
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Paddle webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('Processing error', 500);
        }
    }

    /**
     * Handle completed transaction webhook
     */
    private function handleTransactionCompleted(array $data): void
    {
        $transactionId = $data['id'] ?? null;

        $purchase = QrCodePackagePurchase::where('transaction_id', $transactionId)->first();
        if (!$purchase) {
            Log::warning('Purchase not found for Paddle webhook', [
                'transaction_id' => $transactionId
            ]);
            return;
        }

        // Only update if not already completed
        if ($purchase->status !== 'completed') {

            $purchase->markAsCompleted();

            Log::info('Purchase completed via Paddle webhook', [
                'transaction_id' => $transactionId
            ]);
        }
    }

    /**
     * Handle failed transaction webhook
     */
    private function handleTransactionFailed(array $data): void
    {
        $transactionId = $data['id'] ?? null;

        if (!$transactionId) {
            Log::warning('No transaction_id in Paddle transaction failed webhook', [
                'transaction_id' => $transactionId
            ]);
            return;
        }

        $purchase = QrCodePackagePurchase::where('transaction_id', $transactionId)->first();
        if ($purchase && $purchase->status === 'pending') {
            $purchase->markAsFailed();

            Log::info('Purchase failed via Paddle webhook', [
                'transaction_id' => $transactionId
            ]);
        }
    }

    /**
     * Handle created transaction webhook (optional)
     */
    private function handleTransactionCreated(array $data): void
    {
        $transactionId = $data['id'] ?? null;
        $customData = $data['custom_data'] ?? [];
        $qrCodeId = $customData['qr_code_id'] ?? null;
        $packageId = $customData['package_id'] ?? null;
        $qrCode = QrCode::find($qrCodeId);

        $package = QrCodePackage::find($packageId);

        Log::info('Paddle transaction created webhook', [
            'transaction_id' => $transactionId,
        ]);

        // Optional: Update purchase with transaction ID if not already set
        if ($transactionId) {
            QrCodePackagePurchase::create([
                'qr_code_id' => $qrCodeId,
                'qr_code_package_id' => $packageId,
                'user_id' => $qrCode->user_id,
                'transaction_id' => $transactionId,
                'amount_paid' => $package->price,
                'extended_until' => now()->addMonths($package->duration_months),
                'status' => 'pending',
                'purchased_at' => null,
                'payment_method' => 'paddle',
            ]);
        }
    }
}
