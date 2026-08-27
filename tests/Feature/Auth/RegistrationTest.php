<?php

namespace Tests\Feature\Auth;

use App\Enums\SignupSource;
use App\Filament\Pages\Auth\Register;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Auth\VerifyEmail as PanelVerifyEmail;
use Illuminate\Auth\Notifications\VerifyEmail as FrameworkVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_register_route_sends_you_to_the_panel(): void
    {
        $this->get('/register')
            ->assertRedirect(route('filament.admin.auth.register', absolute: false));
    }

    /**
     * There is exactly one way to create an account, and this asserts the other
     * one stayed shut.
     *
     * Breeze's POST /register survived the move to the panel and nothing linked
     * to it — the GET redirects away — so it could only be reached by posting at
     * it directly. It still worked, and an account created through it had no
     * signup_source, which is worse than an unattributed account because it is
     * indistinguishable from one. This test replaced the one that asserted the
     * scaffold route registered people successfully.
     */
    public function test_there_is_no_second_registration_route(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(405);

        $this->assertGuest();
        $this->assertSame(0, User::query()->where('email', 'test@example.com')->count());
    }

    /**
     * The panel is that one way, and it records the source. Asserted here as
     * well as in StaticOfferTest because this file is where someone reintroducing
     * a second door would look first.
     */
    public function test_the_panel_is_the_registration_path_and_records_the_source(): void
    {
        Notification::fake();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::withQueryParams(['ref' => SignupSource::StaticOffer->value])
            ->test(Register::class)
            ->fillForm([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password',
                'passwordConfirmation' => 'password',
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $this->assertSame(
            SignupSource::StaticOffer,
            User::query()->where('email', 'test@example.com')->sole()->signup_source,
        );
    }

    /**
     * Registering through the panel must produce exactly one verification
     * email.
     *
     * Note the Register in scope is App\Filament\Pages\Auth\Register, not
     * Filament's. This named Filament's until the panel was pointed at a
     * subclass, at which point the test kept passing while no longer exercising
     * the class that actually runs — and the doubled-email bug it guards is
     * exactly the kind that would come back unnoticed through the subclass. The panel notifies the user itself, so anything that listens for
     * the registration event and notifies again doubles up the inbox.
     */
    public function test_registering_through_the_panel_sends_one_verification_email(): void
    {
        Notification::fake();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Register::class)
            ->fillForm([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password',
                'passwordConfirmation' => 'password',
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'test@example.com')->sole();

        Notification::assertSentToTimes($user, PanelVerifyEmail::class, 1);
        Notification::assertNotSentTo($user, FrameworkVerifyEmail::class);
    }
}
