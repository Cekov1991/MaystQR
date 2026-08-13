<?php

namespace App\Providers;

use App\Services\AgentaOS\AgentaOsClient;
use Filament\Events\Auth\Registered as FilamentRegistered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AgentaOsClient::class, function () {
            return new AgentaOsClient(
                config('services.agentaos.key'),
                config('services.agentaos.base_url'),
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
        Event::listen(FilamentRegistered::class, function (FilamentRegistered $event) {
            // Send email verification for Filament registrations
            $event->getUser()->sendEmailVerificationNotification();

            if (Session::has('pending_qr_code')) {
                Session::put('redirect_to_qr_creation', true);
            }
        });
    }
}
