<?php

namespace Tests\Feature\Subscription;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_refund_policy_is_reachable(): void
    {
        $this->get('/refund-policy')
            ->assertOk()
            ->assertSee('Refund Policy');
    }

    public function test_the_terms_describe_the_subscription_rather_than_the_retired_packages(): void
    {
        $response = $this->get('/terms-and-conditions');

        $response->assertOk();
        $response->assertSee('7-day');
        $response->assertSee('$27');
        $response->assertDontSee('[Currency]');
        $response->assertDontSee('Paid extensions');
    }

    /**
     * The parked marketing page advertised dynamic QR codes as free, claimed
     * they were "secure and encrypted", and carried a commented-out "12,000+
     * happy customers" badge — with zero paying customers. It was publicly
     * routable and indexable.
     *
     * AgentaOS asks us to attest that the site displays no false usage claims
     * and that pricing is clear. That page contradicted both, so it is gone
     * rather than parked. This is the guard against restoring the route.
     */
    public function test_the_parked_marketing_page_is_gone(): void
    {
        $this->get('/landing-page')->assertNotFound();
    }

    public function test_the_homepage_still_renders(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_the_privacy_policy_is_reachable(): void
    {
        $this->get('/privacy-policy')
            ->assertOk()
            ->assertSee('Privacy Policy');
    }

    /*
     * AgentaOS is merchant of record and reviews the site before approving live
     * payments. Their go-live form asks us to attest that pricing is "easily
     * accessible and clearly displayed to users before they purchase", and lists
     * "hidden pricing until checkout" as a failure. The price used to exist only
     * in clause 5 of the Terms and on the billing page behind the login, so a
     * reviewer could not learn it without registering.
     *
     * The tests below are that attestation. They must fail if the price stops
     * being reachable while logged out.
     */

    public function test_the_pricing_page_is_publicly_reachable(): void
    {
        $this->get('/pricing')
            ->assertOk()
            ->assertSee('Pricing');
    }

    public function test_the_pricing_page_states_the_price_period_and_tax_treatment(): void
    {
        $this->get('/pricing')
            ->assertOk()
            ->assertSee('$27')
            ->assertSee('per year')
            ->assertSee('Tax is included')
            ->assertSee('merchant of record');
    }

    public function test_the_pricing_page_states_the_trial_needs_no_payment_details(): void
    {
        $this->get('/pricing')
            ->assertOk()
            ->assertSee('7 days of full access', false)
            ->assertSee('no payment details to begin it');
    }

    public function test_the_pricing_page_explains_cancelling_and_the_refund_window(): void
    {
        $this->get('/pricing')
            ->assertOk()
            ->assertSee('14 days')
            ->assertSee('until the end of the period you have')
            ->assertSee('refund-policy', false);
    }

    public function test_the_homepage_shows_the_dynamic_price_without_logging_in(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('$27')
            ->assertSee('per year');
    }

    public function test_the_pricing_page_follows_the_configured_price(): void
    {
        config(['subscription.price' => 42]);

        $this->get('/pricing')
            ->assertOk()
            ->assertSee('$42')
            ->assertDontSee('$27');
    }

    #[DataProvider('publicPageProvider')]
    public function test_a_public_page_links_to_pricing(string $path): void
    {
        $this->get($path)
            ->assertOk()
            ->assertSee(url('/pricing'), false);
    }

    /**
     * The deleted marketing page sold the paid product as free: "Dynamic QR Code
     * Generator: Free & Powerful Solutions", "For Free", and "Free solutions for
     * individuals, SMBs, and enterprises". Dynamic codes cost money, and we have
     * no enterprise tier.
     */
    #[DataProvider('publicPageProvider')]
    public function test_a_public_page_does_not_advertise_dynamic_codes_as_free(string $path): void
    {
        $response = $this->get($path);

        $response->assertOk();
        $response->assertDontSee('For Free');
        $response->assertDontSee('Free &amp; Powerful', false);
        $response->assertDontSee('Free solutions');
    }

    /**
     * The deleted page claimed dynamic QR codes were "secure and encrypted" and
     * "use advanced encryption algorithms to protect your data". A QR code is an
     * encoding, not a cipher.
     *
     * Scoped to that claim rather than the word "encrypted", because the Privacy
     * Policy truthfully says passwords are encrypted and must keep saying so.
     */
    #[DataProvider('publicPageProvider')]
    public function test_a_public_page_makes_no_encryption_claim_about_qr_codes(string $path): void
    {
        $response = $this->get($path);

        $response->assertOk();
        $response->assertDontSee('secure and encrypted');
        $response->assertDontSee('advanced encryption');
        $response->assertDontSee('encryption algorithms');
    }

    /**
     * The go-live form's first attestation is that we display no usage claims or
     * testimonials. It was true only because a "12,000+ happy customers" badge
     * and a "15+ Years" experience badge were commented out on the parked page,
     * one uncomment away from making us liars. Both are deleted; this keeps them
     * from coming back.
     */
    #[DataProvider('publicPageProvider')]
    public function test_a_public_page_carries_no_social_proof_we_cannot_substantiate(string $path): void
    {
        $response = $this->get($path);

        $response->assertOk();
        $response->assertDontSee('happy customers');
        $response->assertDontSee('Of experience in business');
        $response->assertDontSee('testimonial');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function publicPageProvider(): array
    {
        return [
            'homepage' => ['/'],
            'pricing' => ['/pricing'],
            'terms' => ['/terms-and-conditions'],
            'privacy' => ['/privacy-policy'],
            'refunds' => ['/refund-policy'],
        ];
    }

    /**
     * Bootstrap and the scraped template bundle are 11MB of vendor assets under
     * public/landing/. Nothing references them now that the parked marketing
     * layout is deleted, except the two icon files layouts.site still uses.
     */
    #[DataProvider('publicPageProvider')]
    public function test_a_public_page_loads_the_redesigned_stylesheet_and_nothing_else(string $path): void
    {
        $response = $this->get($path);

        $response->assertOk();
        $response->assertSee('css/site.css', false);
        $response->assertDontSee('landing/assets/vendor', false);
        $response->assertDontSee('landing/assets/css/main.css', false);
    }

    /**
     * The footer used to point at #about, #features and #faq, which existed on
     * the old marketing page. The homepage that replaced it has no such
     * sections, so every one of those links went nowhere.
     */
    #[DataProvider('publicPageProvider')]
    public function test_a_public_page_has_no_links_to_the_retired_marketing_anchors(string $path): void
    {
        $response = $this->get($path);

        $response->assertOk();
        $response->assertDontSee('#about', false);
        $response->assertDontSee('#features', false);
        $response->assertDontSee('#faq', false);
    }

    /**
     * The contact address is cited in the Terms and the Privacy Policy as the
     * legal notice address, so it must come from one place rather than being
     * repeated in each document.
     */
    #[DataProvider('publicPageProvider')]
    public function test_a_public_page_shows_the_configured_contact_address(string $path): void
    {
        config(['site.support_email' => 'support@example.test']);

        $this->get($path)
            ->assertOk()
            ->assertSee('support@example.test');
    }

    public function test_the_legal_pages_name_the_full_company_address(): void
    {
        config(['site.company.address' => 'Some Street 1, Skopje, North Macedonia']);

        $this->get('/terms-and-conditions')
            ->assertOk()
            ->assertSee('Some Street 1, Skopje, North Macedonia');

        $this->get('/privacy-policy')
            ->assertOk()
            ->assertSee('Some Street 1, Skopje, North Macedonia');
    }

    /**
     * Section 2 used to describe dynamic codes as having a 7-day validity that
     * you extended by buying a package. That product was retired; section 5
     * already described the subscription, so the document contradicted itself.
     */
    public function test_the_terms_do_not_describe_the_retired_extension_packages(): void
    {
        $this->get('/terms-and-conditions')
            ->assertOk()
            ->assertDontSee('extension package')
            ->assertDontSee('7-day validity')
            ->assertSee('Static QR codes');
    }
}
