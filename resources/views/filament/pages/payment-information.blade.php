<x-filament-panels::page>
    <div class="space-y-6">
        @if(!$hasPayPal)
            <div class="rounded-lg bg-white shadow p-6">
                <div class="text-center">
                    <h3 class="text-lg font-medium text-gray-900">Connect PayPal Account</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        You'll be redirected to PayPal to connect your account for automatic billing.
                    </p>

                    <div class="mt-6">
                        <div id="paypal-button-container"></div>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-lg bg-white shadow p-6">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-success-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">PayPal Connected</h3>
                        <p class="text-sm text-gray-500">Your PayPal account is connected and ready for billing.</p>
                    </div>
                </div>

                <button wire:click="disconnectPayPal" class="mt-4 text-sm text-danger-600 hover:text-danger-800">
                    Disconnect PayPal Account
                </button>
            </div>
        @endif
    </div>

    @push('scripts')
    <script src="https://www.paypal.com/sdk/js?client-id={{ config('services.paypal.client_id') }}&vault=true"></script>
    <script>
        paypal.Buttons({
            createSubscription: function(data, actions) {
                return actions.subscription.create({
                    'plan_id': '{{ config('services.paypal.plan_id') }}'
                });
            },
            onApprove: function(data, actions) {
                // Send the PayPal data to your server
                @this.call('handlePayPalSuccess', data);
            }
        }).render('#paypal-button-container');
    </script>
    @endpush
</x-filament-panels::page>
