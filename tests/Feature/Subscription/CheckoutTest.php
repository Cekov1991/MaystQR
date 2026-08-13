<?php

namespace Tests\Feature\Subscription;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.agentaos.key', 'sk_test_key');
        config()->set('services.agentaos.payment_link_id', 'link_uuid_123');
        Http::preventStrayRequests();
    }

    private function fakeCheckoutCreation(): void
    {
        Http::fake([
            '*/gateway/sessions' => Http::response([
                'id' => 'checkout_uuid',
                'session_id' => 'sess_xyz',
                'currency' => 'USD',
                'checkoutUrl' => 'https://app.agentaos.ai/pay/sess_xyz',
            ], 201),
        ]);
    }

    public function test_subscribing_redirects_to_the_hosted_checkout(): void
    {
        $this->fakeCheckoutCreation();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/billing/subscribe')
            ->assertRedirect('https://app.agentaos.ai/pay/sess_xyz');
    }

    public function test_the_checkout_carries_the_user_id_so_the_payment_can_be_attributed(): void
    {
        $this->fakeCheckoutCreation();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/billing/subscribe');

        Http::assertSent(function (Request $request) use ($user): bool {
            $body = $request->data();

            return str_contains($request->url(), '/gateway/sessions')
                && $request->hasHeader('x-api-key', 'sk_test_key')
                && $body['linkId'] === 'link_uuid_123'
                && $body['buyerEmail'] === $user->email
                && $body['metadata']['user_id'] === (string) $user->id;
        });
    }

    public function test_the_return_urls_come_from_the_app_url_not_the_request_host(): void
    {
        $this->fakeCheckoutCreation();
        config()->set('app.url', 'https://easyqr.example/');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/billing/subscribe');

        Http::assertSent(function (Request $request): bool {
            $body = $request->data();

            return $body['successUrl'] === 'https://easyqr.example/billing/success'
                && $body['cancelUrl'] === 'https://easyqr.example/billing/cancel';
        });
    }

    public function test_a_pending_subscription_row_is_recorded_before_the_redirect(): void
    {
        $this->fakeCheckoutCreation();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/billing/subscribe');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'checkout_session_id' => 'sess_xyz',
            'status' => 'incomplete',
        ]);
    }

    public function test_paying_does_not_happen_and_nothing_is_recorded_when_the_api_fails(): void
    {
        Http::fake([
            '*/gateway/sessions' => Http::response(
                ['statusCode' => 400, 'message' => 'amount is required'],
                400,
            ),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/billing/subscribe')
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertTrue($user->fresh()->isTrialing());
    }

    public function test_checkout_is_refused_when_no_payment_link_is_configured(): void
    {
        config()->set('services.agentaos.payment_link_id', null);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/billing/subscribe')
            ->assertRedirect()
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }

    public function test_a_guest_cannot_start_a_checkout(): void
    {
        $this->post('/billing/subscribe')->assertRedirect('/login');

        Http::assertNothingSent();
    }
}
