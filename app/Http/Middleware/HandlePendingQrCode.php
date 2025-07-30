<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class HandlePendingQrCode
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Check if user just logged in/registered and has pending QR code
        if (Auth::check() && Session::has('pending_qr_code') && $request->routeIs('filament.admin.*')) {
            // Only redirect if we're going to the dashboard or a similar route
            if ($request->routeIs('filament.admin.pages.dashboard') || $request->url() === url('/admin')) {
                return redirect('/admin/qr-codes/create-from-session');
            }
        }

        return $response;
    }
}