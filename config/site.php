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
    | Operator
    |--------------------------------------------------------------------------
    |
    | The person legally behind the service: the contracting party in the Terms
    | and the data controller in the Privacy Policy.
    |
    | This must match the account holder at AgentaOS. They are our merchant of
    | record, so the question their review answers is who they are selling on
    | behalf of — a company name here against an individual there is exactly the
    | mismatch that stalls the application.
    |
    | The service is operated by an individual, so this is a legal name and not a
    | brand. "Mayst Impact" is a brand and belongs in the footer credit below,
    | never in the legal documents.
    |
    | Was 'company' with a default of "Mayst Impact". If SITE_COMPANY_NAME or
    | SITE_COMPANY_ADDRESS are still set in a deployed .env they are now ignored;
    | delete them so they cannot mislead later.
    |
    */

    'operator' => [
        'name' => env('SITE_OPERATOR_NAME', 'Stefan Cekov'),
        'address' => env('SITE_OPERATOR_ADDRESS', 'Vladimir Komarov 25/4-16, Skopje, North Macedonia'),
        'tax_id' => env('SITE_OPERATOR_TAX_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Processors
    |--------------------------------------------------------------------------
    |
    | Every company that processes personal data on our behalf, as named in the
    | Privacy Policy. The policy renders this list rather than repeating it in
    | prose, so a processor cannot be added to the stack without appearing in the
    | document — and cannot be dropped from the document while still in use.
    |
    | Adding a row here is a disclosure, not a config change. If you add a
    | processor, confirm its data processing agreement incorporates the Standard
    | Contractual Clauses before shipping, because section 6 of the policy relies
    | on them for transfers out of the EEA.
    |
    */

    'processors' => [
        [
            'name' => 'Laravel Cloud',
            'role' => 'Application hosting and database',
            'location' => 'United States',
        ],
        [
            'name' => 'Cloudflare',
            'role' => 'Content delivery, TLS, bot protection, and the approximate country of a QR code scan',
            'location' => 'Global edge network',
        ],
        [
            'name' => 'Resend',
            'role' => 'Delivery of account and billing email',
            'location' => 'United States',
        ],
        [
            'name' => 'AgentaOS',
            'role' => 'Payment processing as merchant of record',
            'location' => 'See their privacy policy',
        ],
        [
            'name' => 'Bunny Fonts',
            'role' => 'Serving the web fonts used on our public pages',
            'location' => 'European Union',
        ],
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
