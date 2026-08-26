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

        /*
         * The panel sends the verification email itself, because the admin
         * panel is configured with emailVerification(). Nothing is sent from
         * here: Filament's Register page fires this event and then notifies
         * the user on the very next line, so a second send from this listener
         * put two near-identical emails in the same inbox.
         */
        Event::listen(FilamentRegistered::class, function (FilamentRegistered $event) {
            if (Session::has('pending_qr_code')) {
                Session::put('redirect_to_qr_creation', true);
            }
        });
    }
}
