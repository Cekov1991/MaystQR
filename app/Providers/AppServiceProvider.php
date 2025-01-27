<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use App\Services\IpGeolocationService;
use Illuminate\Http\Client\Factory as Http;
use App\Models\Subscription;
use App\Observers\SubscriptionObserver;

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
        Subscription::observe(SubscriptionObserver::class);
    }
}
