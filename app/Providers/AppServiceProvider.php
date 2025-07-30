<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use App\Services\IpGeolocationService;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Facades\Event;
use Filament\Events\Auth\Registered;
use Illuminate\Support\Facades\Session;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(IpGeolocationService::class, function ($app) {
            return new IpGeolocationService(
                $app->make(Http::class),
                config('services.ipgeolocation.key')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();

        // Listen for Filament registration events
        Event::listen(Registered::class, function (Registered $event) {

            if (Session::has('pending_qr_code')) {
                Session::put('redirect_to_qr_creation', true);
            }
        });
    }
}
