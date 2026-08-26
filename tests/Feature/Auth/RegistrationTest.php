<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Auth\VerifyEmail as PanelVerifyEmail;
use Filament\Pages\Auth\Register;
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

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    /**
     * Registering through the panel must produce exactly one verification
     * email. The panel notifies the user itself, so anything that listens for
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
