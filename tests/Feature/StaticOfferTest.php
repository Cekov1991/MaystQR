<?php

namespace Tests\Feature;

use App\Enums\SignupSource;
use App\Enums\TrackedEvent;
use App\Filament\Pages\Auth\Register;
use App\Models\SiteEvent;
use App\Models\User;
use App\Support\SubscriptionPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The upgrade offer under a freshly downloaded static code, and the one piece of
 * per-person attribution in the whole funnel.
 *
 * The homepage had no test of its own before the event work, so some of what
 * follows is simply its first coverage. The rest guards two things that are easy
 * to break silently: a price that misdescribes what the customer is charged, and
 * a `?ref=` parameter turning into a column anyone can write anything into.
 */
class StaticOfferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_the_homepage_renders(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_the_generator_returns_both_formats_as_data_uris(): void
    {
        $this->postJson(route('qr.instant'), ['url' => 'https://example.com'])
            ->assertOk()
            ->assertJsonStructure(['png', 'svg']);

        $data = $this->postJson(route('qr.instant'), ['url' => 'https://example.com'])->json();

        $this->assertStringStartsWith('data:image/png;base64,', $data['png']);
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $data['svg']);
    }

    public function test_the_offer_is_in_the_markup_for_a_visitor(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="static-offer"', false)
            ->assertSee('That code can never be changed');
    }

    /**
     * The panel ships hidden. It is revealed by script only once a download has
     * started, so shipping it visible would put the offer in front of the
     * download button — the opposite of the intent.
     */
    public function test_the_offer_ships_hidden(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="static-offer" class="eq-offer" hidden', false);
    }

    /**
     * Someone signed in already has an account; the pitch would be for something
     * they have. It is removed from the markup rather than hidden, so it cannot
     * be revealed by script that does not know better.
     */
    public function test_the_offer_is_absent_for_a_signed_in_user(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertDontSee('id="static-offer"', false);
    }

    /**
     * Every figure comes from config. The monthly number in particular must be
     * derived: hardcoding it is how a price change ships a homepage that quotes
     * the old one.
     */
    public function test_the_offer_quotes_the_price_from_config(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(SubscriptionPrice::monthlyEquivalent())
            ->assertSee(SubscriptionPrice::formatted())
            ->assertSee(config('subscription.trial_days').'-day free trial');
    }

    /**
     * The load-bearing pricing test. Nobody is charged the monthly figure and
     * there is no month they could cancel after, so it is the most misleading
     * number on the site if it ever appears without the real charge beside it.
     * PublicPagesTest guards the claims AgentaOS reviews; this guards this one.
     */
    public function test_the_monthly_figure_never_appears_without_the_annual_charge(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Billed '.SubscriptionPrice::formatted().' once a year');
    }

    public function test_a_monthly_billed_plan_has_no_monthly_equivalent(): void
    {
        config(['subscription.billing_interval' => 'month']);

        $this->assertNull(SubscriptionPrice::monthlyEquivalent());
    }

    /**
     * Rounded up, to the cent. Rounding down would quote twelve instalments
     * adding up to less than the amount actually taken.
     */
    public function test_the_monthly_equivalent_rounds_up(): void
    {
        config(['subscription.price' => 27, 'subscription.currency' => 'USD']);

        $this->assertSame('$2.25', SubscriptionPrice::monthlyEquivalent());

        config(['subscription.price' => 100]);

        $this->assertSame('$8.34', SubscriptionPrice::monthlyEquivalent());
    }

    public function test_the_offer_links_to_registration_carrying_its_ref(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('ref='.SignupSource::StaticOffer->value, false);
    }

    /**
     * The quiet inline link stays where it is, and carries its own ref. The pair
     * is the experiment: both are shown to everyone, so comparing their
     * registration rates asks whether the loud offer beats an unobtrusive line of
     * text without suppressing either for anybody.
     */
    public function test_the_quiet_inline_link_still_exists_and_is_tagged(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Create a dynamic QR')
            ->assertSee('ref='.SignupSource::StaticInline->value, false);
    }

    /**
     * Both arms are reachable on the same page for the same visitor. If one ever
     * stops rendering, the comparison silently becomes a measurement of nothing.
     */
    public function test_both_arms_are_offered_to_the_same_visitor(): void
    {
        $response = $this->get('/')->assertOk();

        foreach (SignupSource::cases() as $source) {
            $response->assertSee('ref='.$source->value, false);
        }
    }

    public function test_a_registration_from_the_quiet_link_records_its_own_source(): void
    {
        Livewire::withQueryParams(['ref' => SignupSource::StaticInline->value])
            ->test(Register::class)
            ->fillForm([
                'name' => 'Edsger',
                'email' => 'edsger@example.com',
                'password' => 'password-that-is-long',
                'passwordConfirmation' => 'password-that-is-long',
            ])
            ->call('register');

        $this->assertSame(
            SignupSource::StaticInline,
            User::query()->where('email', 'edsger@example.com')->sole()->signup_source,
        );
    }

    /**
     * No holdout was built, so no event may carry an arm. A `variant` reaching
     * the table would mean a suppression mechanism had been added without the
     * endpoint's allowlist being added with it.
     */
    public function test_no_event_carries_an_experiment_arm(): void
    {
        $this->postJson(route('events.log'), [
            'event' => TrackedEvent::OfferShown->value,
            'variant' => 'holdout',
        ])->assertStatus(422);

        $this->postJson(route('events.log'), ['event' => TrackedEvent::OfferShown->value])
            ->assertNoContent();

        $this->assertNull(SiteEvent::query()->sole()->variant);
    }

    public function test_the_offer_reports_its_events_to_the_endpoint(): void
    {
        $response = $this->get('/')->assertOk();

        foreach ([TrackedEvent::OfferShown, TrackedEvent::OfferDismissed, TrackedEvent::OfferClicked] as $event) {
            $response->assertSee($event->value);
        }
    }

    /**
     * Dismissal must not be a cookie. A marketing cookie contradicts section 2c
     * of the Privacy Policy and would drag the whole site behind a real consent
     * gate for the sake of one hidden panel.
     */
    public function test_dismissal_is_remembered_in_local_storage_not_a_cookie(): void
    {
        $response = $this->get('/')->assertOk();

        $response->assertSee('localStorage', false);
        $this->assertStringNotContainsString(
            'document.cookie',
            $response->getContent(),
            'Remembering the dismissal in a cookie would make section 2c of the Privacy Policy false.',
        );
    }

    public function test_a_registration_from_the_offer_records_its_source(): void
    {
        $this->withoutMiddleware()
            ->get(route('filament.admin.auth.register', ['ref' => SignupSource::StaticOffer->value]));

        Livewire::withQueryParams(['ref' => SignupSource::StaticOffer->value])
            ->test(Register::class)
            ->fillForm([
                'name' => 'Ada',
                'email' => 'ada@example.com',
                'password' => 'password-that-is-long',
                'passwordConfirmation' => 'password-that-is-long',
            ])
            ->call('register');

        $this->assertSame(
            SignupSource::StaticOffer,
            User::query()->where('email', 'ada@example.com')->sole()->signup_source,
        );
    }

    /**
     * The reason this is an enum and not a string column. `?ref=` comes off a URL
     * a stranger can put anything into, and an unvalidated column would become a
     * free-text sink filled by whoever felt like filling it.
     */
    public function test_an_invented_ref_is_discarded_rather_than_stored(): void
    {
        Livewire::withQueryParams(['ref' => 'not-a-source-we-published'])
            ->test(Register::class)
            ->fillForm([
                'name' => 'Grace',
                'email' => 'grace@example.com',
                'password' => 'password-that-is-long',
                'passwordConfirmation' => 'password-that-is-long',
            ])
            ->call('register');

        $this->assertNull(User::query()->where('email', 'grace@example.com')->sole()->signup_source);
    }

    public function test_a_registration_with_no_ref_records_no_source(): void
    {
        Livewire::test(Register::class)
            ->fillForm([
                'name' => 'Alan',
                'email' => 'alan@example.com',
                'password' => 'password-that-is-long',
                'passwordConfirmation' => 'password-that-is-long',
            ])
            ->call('register');

        $this->assertNull(User::query()->where('email', 'alan@example.com')->sole()->signup_source);
    }

    /**
     * The column is set from an allowlisted query parameter, never from posted
     * form input. It is not in $fillable, but note that that is not what stops
     * this: AppServiceProvider calls Model::unguard(), so mass-assignment
     * guarding is off application-wide and fill() would happily write it. What
     * stops it is that `signup_source` is not in the form schema, so
     * $this->form->getState() discards it before registration ever sees it.
     */
    public function test_a_source_posted_through_the_form_is_discarded(): void
    {
        Livewire::test(Register::class)
            ->set('data.name', 'Eve')
            ->set('data.email', 'eve@example.com')
            ->set('data.password', 'password-that-is-long')
            ->set('data.passwordConfirmation', 'password-that-is-long')
            ->set('data.signup_source', SignupSource::StaticOffer->value)
            ->call('register');

        $this->assertNull(User::query()->where('email', 'eve@example.com')->sole()->signup_source);
    }

    /**
     * The component property is public, so Livewire lets the browser assign it
     * directly — bypassing the check done at mount. It is therefore resolved
     * through the enum again at the write site. Without that, the allowlist would
     * only ever have applied to the honest path.
     */
    public function test_a_tampered_component_property_is_discarded(): void
    {
        Livewire::test(Register::class)
            ->set('signupSource', 'whatever-i-typed')
            ->fillForm([
                'name' => 'Mallory',
                'email' => 'mallory@example.com',
                'password' => 'password-that-is-long',
                'passwordConfirmation' => 'password-that-is-long',
            ])
            ->call('register');

        $this->assertNull(User::query()->where('email', 'mallory@example.com')->sole()->signup_source);
    }

    /**
     * The documented gap, asserted so it is a known state rather than a surprise.
     * If someone removes the unguard() call, this test fails and the User model's
     * $fillable list starts meaning what it says.
     */
    public function test_mass_assignment_guarding_is_currently_disabled_application_wide(): void
    {
        $user = new User;
        $user->fill(['signup_source' => SignupSource::StaticOffer->value]);

        $this->assertSame(
            SignupSource::StaticOffer,
            $user->signup_source,
            'Model::unguard() in AppServiceProvider appears to have been removed. That is an '
            .'improvement — update this test and the User model docblock to match.',
        );
    }

    public function test_the_privacy_policy_discloses_the_source_column(): void
    {
        $this->get('/privacy-policy')
            ->assertOk()
            ->assertSee('Which part of our site sent you to the registration form')
            ->assertSee(SignupSource::StaticOffer->value);
    }
}
