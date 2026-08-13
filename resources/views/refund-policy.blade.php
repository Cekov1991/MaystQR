@extends('layouts.qr')

@section('title', 'Refund Policy - ' . config('app.name'))
@section('description', 'How refunds work for ' . config('app.name') . ' subscriptions.')

@section('content')
<section class="section" style="padding-top: 120px;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h1 class="mb-2">Refund Policy</h1>
                <p class="text-muted mb-5">Last updated {{ now()->format('j F Y') }}</p>

                <div class="mb-5">
                    <h2 class="h3 mb-3">1. The free trial comes first</h2>
                    <p>
                        Every new account gets {{ config('subscription.trial_days') }} days of full access
                        without entering any payment details. The trial exists so you can decide whether
                        the service works for you before paying anything.
                    </p>
                </div>

                <div class="mb-5">
                    <h2 class="h3 mb-3">2. 14-day refund window</h2>
                    <p>
                        If you subscribe and change your mind, contact us within <strong>14 days</strong> of
                        the payment and we will refund it in full. This applies to a first subscription and
                        to each yearly renewal.
                    </p>
                    <p>
                        If you are a consumer in the EU or UK, this reflects your statutory right of
                        withdrawal; nothing in this policy limits rights you have by law.
                    </p>
                </div>

                <div class="mb-5">
                    <h2 class="h3 mb-3">3. After 14 days</h2>
                    <p>
                        We do not refund the remainder of a yearly period once the window has passed.
                        You can cancel at any time to stop the next renewal, and you keep full access
                        until the end of the period you have already paid for.
                    </p>
                    <p>
                        If the service fails in a way we cannot resolve — for example your dynamic QR
                        codes stop resolving through our fault — contact us and we will refund a fair
                        share of the period affected.
                    </p>
                </div>

                <div class="mb-5">
                    <h2 class="h3 mb-3">4. Cancelling</h2>
                    <p>
                        Cancel from the <strong>Subscription</strong> page in your account. Cancelling stops
                        future renewals; it is not itself a refund request. Your static QR codes remain free
                        and keep working regardless.
                    </p>
                </div>

                <div class="mb-5">
                    <h2 class="h3 mb-3">5. How to request a refund</h2>
                    <p>
                        Email <a href="mailto:mayst.impact@gmail.com">mayst.impact@gmail.com</a> from the
                        address on your account, telling us which payment you mean. We aim to respond within
                        two business days, and approved refunds return to the original payment method,
                        typically within 5–10 business days depending on your bank.
                    </p>
                    <p>
                        Payments are processed by AgentaOS as merchant of record, so the refund is issued
                        through them and may appear on your statement under their name.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
