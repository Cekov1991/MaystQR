<?php

namespace Tests\Feature\Subscription;

use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_the_parked_landing_page_still_renders_without_the_package_model(): void
    {
        $this->get('/landing-page')->assertOk();
    }

    public function test_the_homepage_still_renders(): void
    {
        $this->get('/')->assertOk();
    }
}
