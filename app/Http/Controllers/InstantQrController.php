<?php

namespace App\Http\Controllers;

use App\Rules\ValidQrUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;

class InstantQrController extends Controller
{
    public function index(): View
    {
        return view('home');
    }

    /**
     * Generate a static QR code on the fly. Nothing is persisted — no
     * database row and no stored image; the code only exists in the response.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048', new ValidQrUrl],
        ]);

        $png = QrCodeGenerator::format('png')
            ->size(600)
            ->errorCorrection('M')
            ->generate($validated['url']);

        $svg = QrCodeGenerator::format('svg')
            ->size(600)
            ->errorCorrection('M')
            ->generate($validated['url']);

        return response()->json([
            'png' => 'data:image/png;base64,' . base64_encode((string) $png),
            'svg' => 'data:image/svg+xml;base64,' . base64_encode((string) $svg),
        ]);
    }
}
