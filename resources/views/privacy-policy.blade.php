@extends('layouts.site')

@section('title', 'Privacy Policy - ' . config('app.name'))
@section('description', 'How ' . config('app.name') . ' collects, uses, and protects your personal information.')

@section('content')

    <div class="eq-prose">
        <h1 class="eq-h1">Privacy Policy</h1>
        <p class="eq-prose-updated">Last updated 13 August 2026 · {{ config('site.domain') }}</p>

        <h2>1. Introduction</h2>
        <p>Welcome to {{ config('site.domain') }} (“we,” “our,” or “us”).</p>
        <p>
            We respect your privacy and are committed to protecting your personal information. This Privacy Policy
            explains how we collect, use, and protect your data when you use our website and services.
        </p>
        <p>By using our Service, you agree to the practices described in this Policy.</p>

        <h2>2. Information We Collect</h2>

        <h3>a) Information you provide</h3>
        <p>When registering for our platform, we collect:</p>
        <ul>
            <li>Name (first and/or last)</li>
            <li>Email address</li>
            <li>Password (encrypted; we cannot see your actual password)</li>
        </ul>
        <p>You may also provide optional information, such as custom QR code content and uploaded logos.</p>

        <h3>b) Information collected automatically</h3>
        <p>When you use our website, we automatically collect:</p>
        <ul>
            <li>IP address</li>
            <li>Browser type &amp; version</li>
            <li>Pages visited and time spent</li>
            <li>Device information</li>
            <li>Basic QR code scan statistics (for dynamic QR codes)</li>
        </ul>

        <h3>c) Cookies</h3>
        <p>
            We use only the cookies needed to run the site: a session cookie that keeps you signed in,
            a security cookie that protects forms against cross-site request forgery, and a cookie that
            records that you have seen our cookie notice.
        </p>
        <p>
            <strong>We do not use advertising or analytics cookies, and we run no third-party tracking
            on this site.</strong>
        </p>

        <h2>3. How We Use Your Information</h2>
        <p>We use your information to:</p>
        <ul>
            <li>Provide and manage your account</li>
            <li>Generate and maintain your QR codes</li>
            <li>Process subscription payments and renewals</li>
            <li>Send important service updates</li>
            <li>Improve our website and services</li>
            <li>Respond to customer support requests</li>
        </ul>
        <p><strong>We do not sell your personal data.</strong></p>

        <h2>4. Email Communication</h2>
        <p>By creating an account, you agree to receive:</p>
        <ul>
            <li>Transactional emails (e.g., account confirmation, password reset, service updates)</li>
            <li>Optional product updates or promotions (only if you opt in; you can unsubscribe anytime)</li>
        </ul>

        <h2>5. Data Retention</h2>
        <p>We keep your data for as long as your account is active.</p>
        <p>
            If you delete your account, we remove your personal details from our active systems, except where
            required by law.
        </p>

        <h2>6. Sharing Your Information</h2>
        <p>We may share your data only with:</p>
        <ul>
            <li>Service providers (e.g., hosting, payment processing) to operate the Service</li>
            <li>Legal authorities if required by law</li>
        </ul>
        <p><strong>We do not share your information with advertisers.</strong></p>

        <h2>7. Your Rights</h2>
        <p>Depending on your location (e.g., under GDPR or CCPA), you have the right to:</p>
        <ul>
            <li>Access the data we hold about you</li>
            <li>Request correction or deletion of your data</li>
            <li>Restrict or object to certain processing</li>
            <li>Request a copy of your data in a portable format</li>
            <li>Withdraw consent for marketing emails</li>
        </ul>
        <p>
            To exercise these rights, contact us at
            <a href="mailto:{{ config('site.support_email') }}">{{ config('site.support_email') }}</a>.
        </p>

        <h2>8. Data Security</h2>
        <p>We take reasonable measures to protect your information, including:</p>
        <ul>
            <li>Encrypted passwords</li>
            <li>Secure HTTPS connection</li>
            <li>Restricted database access</li>
        </ul>
        <p>However, no online service can be 100% secure.</p>

        <h2>9. International Users</h2>
        <p>
            If you access our Service from outside The Republic of North Macedonia, your data will be stored and
            processed in The Republic of North Macedonia, where data protection laws may differ.
        </p>

        <h2>10. Changes to This Policy</h2>
        <p>We may update this Privacy Policy occasionally.</p>
        <p>
            If we make significant changes, we will notify you by email or through the website before the changes
            take effect.
        </p>

        <h2>11. Contact Us</h2>
        <p>If you have any questions about this Privacy Policy, contact us at:</p>
        <ul>
            <li><strong>Email:</strong> <a href="mailto:{{ config('site.support_email') }}">{{ config('site.support_email') }}</a></li>
            <li><strong>Address:</strong> {{ config('site.company.address') }}</li>
        </ul>
    </div>

@endsection
