@php
    $warning = auth()->user()->getDynamicQrCodePriceWarning();
    $hasPaymentInfo = auth()->user()->hasPaymentInformation();
@endphp

@if($warning || !$hasPaymentInfo)
    <div class="rounded-lg bg-warning-100 border-l-4 border-warning-500 p-4 mb-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-warning-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-lg font-medium text-warning-900">
                    Action Required
                </h3>
                <div class="mt-2 text-warning-800">
                    @if(!$hasPaymentInfo)
                        <p class="font-medium">PayPal Connection Required</p>
                        <p class="mt-1">To create dynamic QR codes, you need to connect your PayPal account first.</p>
                        <a href="{{ route('filament.admin.pages.payment-information') }}"
                           class="mt-3 inline-flex items-center px-4 py-2 border text-sm font-medium rounded-md shadow-sm text-red  hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black">
                            Connect PayPal Account
                        </a>
                    @endif

                    @if($warning)
                        <p class="@if(!$hasPaymentInfo) mt-4 @endif font-medium">Subscription Update</p>
                        <p class="mt-1">{{ $warning }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
