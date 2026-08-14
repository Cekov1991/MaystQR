<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * There is one login screen and it is the panel's. Laravel sends
     * unauthenticated users to route('login'), so this route has to keep
     * existing — it just must not render a second, differently designed one.
     */
    public function test_the_login_route_sends_you_to_the_panel(): void
    {
        $this->get('/login')
            ->assertRedirect(route('filament.admin.auth.login', absolute: false));
    }

    /**
     * The Breeze dashboard was the scaffold placeholder — a Laravel logo and
     * "You're logged in!". Breeze's controllers still redirect here after
     * login and registration, so it has to land somewhere real.
     */
    public function test_the_dashboard_route_sends_you_to_the_panel(): void
    {
        $this->get('/dashboard')
            ->assertRedirect(route('filament.admin.pages.dashboard', absolute: false));
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
