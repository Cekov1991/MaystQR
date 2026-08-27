<?php

namespace App\Http\Controllers;

use App\Enums\TrackedEvent;
use App\Models\QrCode;
use App\Rules\ValidQrUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstantQrController extends Controller
{
    public function index(): View
    {
        return view('home');
    }

    /**
     * Generate a static QR code on the fly.
     *
     * Nothing about the code is persisted: no row holding the URL and no stored
     * image, so it exists only in this response. The one row written is a bare
     * count — see TrackedEvent, which records that a code was generated and
     * nothing whatsoever about which code, or by whom. That distinction is what
     * keeps the homepage's "we never store your code or its link" true.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048', new ValidQrUrl],
        ]);

        $options = ['size' => 600, 'errorCorrection' => 'M', 'style' => QrCode::DEFAULT_STYLE];

        $png = QrCode::buildGenerator($options + ['format' => 'png'])->generate($validated['url']);
        $svg = QrCode::buildGenerator($options + ['format' => 'svg'])->generate($validated['url']);

        TrackedEvent::StaticQrGenerated->record();

        return response()->json([
            'png' => 'data:image/png;base64,'.base64_encode((string) $png),
            'svg' => 'data:image/svg+xml;base64,'.base64_encode((string) $svg),
        ]);
    }
}
