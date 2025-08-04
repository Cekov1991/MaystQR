<!DOCTYPE html>
<html lang="en">
<head>
    <title>Fastlane - PayPal Integration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="{{ asset('css/fastlane-styles.css') }}" />

    <script
        src="{{ $sdkUrl }}?client-id={{ $clientId }}&components=buttons%2Cfastlane"
        data-sdk-client-token="{{ $clientToken }}"
        defer
    ></script>
</head>
<body>
    <form>
        <h1>Fastlane - PayPal Integration</h1>

        <section id="customer" class="active visited">
            <div class="header">
                <h2>Customer</h2>
                <button id="email-edit-button" type="button" class="edit-button">Edit</button>
            </div>
            <div class="summary"></div>
            <div class="email-container">
                <fieldset class="email-input-with-watermark">
                    <input
                        id="email-input"
                        name="email"
                        type="email"
                        placeholder="Email"
                        autocomplete="email"
                    />
                    <div id="watermark-container"></div>
                </fieldset>
                <button id="email-submit-button" type="button" class="submit-button" disabled>
                    Continue
                </button>
            </div>
        </section>


        <section id="payment">
            <div class="header">
                <h2>Payment</h2>
                <button id="payment-edit-button" type="button" class="edit-button">Edit</button>
            </div>
            <fieldset>
                <div id="payment-component"></div>
            </fieldset>
        </section>

        <button id="checkout-button" type="button" class="submit-button">Checkout</button>
    </form>

    <script src="{{ asset('js/fastlane-init.js') }}" defer></script>
</body>
</html>