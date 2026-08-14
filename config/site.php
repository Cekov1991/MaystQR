<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Contact
    |--------------------------------------------------------------------------
    |
    | The address shown in the footer and named in the Terms, Privacy Policy and
    | Refund Policy. It was previously hardcoded in seven places, which meant a
    | change had to be made in seven places and the legal notice address could
    | silently drift from the one in the footer.
    |
    | This is a public, legally-cited address, so it should be on our own
    | domain rather than a free mailbox.
    |
    */

    'support_email' => env('SITE_SUPPORT_EMAIL', 'mayst.impact@gmail.com'),

    /*
    |--------------------------------------------------------------------------
    | Domain
    |--------------------------------------------------------------------------
    |
    | The public domain, as named in the Terms and the Privacy Policy. Those
    | documents identify the Service by this name, so it must match the domain
    | customers actually reach.
    |
    */

    'domain' => env('SITE_DOMAIN', 'easy-qr-code.com'),

    /*
    |--------------------------------------------------------------------------
    | Company
    |--------------------------------------------------------------------------
    |
    | The legal entity behind the service. Named in the Terms as the contracting
    | party and in the Privacy Policy as the data controller, so it must be the
    | registered name rather than the product name.
    |
    */

    'company' => [
        'name' => env('SITE_COMPANY_NAME', 'Mayst Impact'),
        'address' => env('SITE_COMPANY_ADDRESS', 'Vladimir Komarov 25/4-16, Skopje, North Macedonia'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Footer credit
    |--------------------------------------------------------------------------
    |
    | The optional "Powered by" line. Leave the URL blank to drop the credit
    | from the footer entirely.
    |
    */

    'credit' => [
        'name' => env('SITE_CREDIT_NAME', 'Mayst Impact'),
        'url' => env('SITE_CREDIT_URL', 'https://maystimpact.mk'),
    ],

];
