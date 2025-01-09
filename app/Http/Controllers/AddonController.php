<?php

namespace App\Http\Controllers;

use App\Models\Addon;
use App\Models\QrCode;
use App\Services\AddonPurchaseService;
use Exception;
use Illuminate\Http\Request;

class AddonController extends Controller
{
    public function purchase(
        Request $request,
        Addon $addon,
        AddonPurchaseService $addonService
    ) {
        $qrCode = null;
        if ($request->has('qr_code_id')) {
            $qrCode = QrCode::findOrFail($request->qr_code_id);
        }

        try {
            $userAddon = $addonService->purchaseAddon(
                auth()->user(),
                $addon,
                $qrCode
            );

            return response()->json([
                'message' => 'Addon purchased successfully',
                'user_addon' => $userAddon
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
