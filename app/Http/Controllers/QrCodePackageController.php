<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use App\Models\QrCodePackage;
use App\Models\QrCodePackagePurchase;
use App\Services\PayPalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QrCodePackageController extends Controller
{
    public function __construct(
        private PayPalService $paypalService
    ) {}

    public function show(QrCode $qrCode, QrCodePackage $package)
    {
        // Check if user is the owner
        if ($qrCode->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this QR code.');
        }

        return view('qr-package-purchase', compact('qrCode', 'package'));
    }

    public function purchase(Request $request, QrCode $qrCode, QrCodePackage $package)
    {
        // Check if user is the owner
        if ($qrCode->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this QR code.');
        }

        try {
            // Calculate new expiration date
            $newExpirationDate = $qrCode->isExpired()
                ? now()->addMonths($package->duration_months)
                : $qrCode->expires_at->addMonths($package->duration_months);

            // Create the purchase record
            $purchase = QrCodePackagePurchase::create([
                'qr_code_id' => $qrCode->id,
                'qr_code_package_id' => $package->id,
                'user_id' => auth()->id(),
                'amount_paid' => $package->price,
                'extended_until' => $newExpirationDate,
                'status' => 'pending',
                'purchased_at' => now(),
            ]);

            // Create PayPal order
            $order = $this->paypalService->createPackagePurchase($package->price, $purchase->id);

            // Store PayPal order ID
            $purchase->update(['transaction_id' => $order->id]);

            // Redirect to PayPal checkout
            return redirect($order->links[1]->href);

        } catch (\Exception $e) {
            dd($e);
            return back()->with('error', 'Unable to process purchase. Please try again.');
        }
    }

    public function success(Request $request)
    {
        try {
            $result = $this->paypalService->capturePayment($request->token);

            // Find the purchase by transaction ID
            $purchase = QrCodePackagePurchase::where('transaction_id', $result->id)->firstOrFail();

            // Mark purchase as completed and extend QR code
            DB::transaction(function () use ($purchase, $result) {
                $purchase->update([
                    'status' => 'completed',
                    'purchased_at' => now(),
                    'payment_method' => 'paypal',
                ]);

                // Extend the QR code validity
                $purchase->qrCode->update([
                    'expires_at' => $purchase->extended_until
                ]);
            });

            return redirect()->route('filament.admin.resources.qr-codes.view', $purchase->qr_code)
                ->with('success', 'QR code extended successfully!');

        } catch (\Exception $e) {
            return redirect()->route('filament.admin.resources.qr-codes.index')
                ->with('error', 'Payment processing failed. Please contact support.');
        }
    }

    public function cancel(Request $request)
    {
        // Find and mark purchase as failed
        if ($request->has('token')) {
            $purchase = QrCodePackagePurchase::where('transaction_id', $request->token)->first();
            if ($purchase) {
                $purchase->markAsFailed();
            }
        }

        return redirect()->route('filament.admin.resources.qr-codes.index')
            ->with('info', 'Purchase cancelled.');
    }
}