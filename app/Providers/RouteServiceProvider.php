<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //

        RateLimiter::for('qr-downloads', function (Request $request) {
            return [
                Limit::perMinute(60)->by($request->user()->id), // 60 downloads per minute
                Limit::perDay(1000)->by($request->user()->id),  // 1000 downloads per day
            ];
        });
    }
}
