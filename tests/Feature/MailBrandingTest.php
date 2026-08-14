<?php

namespace Tests\Feature;

use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/**
 * What our outgoing mail says it is and looks like.
 *
 * Laravel ships `Example` and `hello@example.com` as the global From defaults.
 * Neither fails loudly: an environment that forgets MAIL_FROM_NAME delivers real
 * mail signed "Example", which is what lands in the inbox beside the subject
 * line. The mail header carried Laravel's own hosted logo under the same kind of
 * silent default, so these tests pin both to us.
 */
class MailBrandingTest extends TestCase
{
    use RefreshDatabase;

    private const LOGO = 'images/easy-qr-logo-trim.png';

    /**
     * Re-evaluating config/mail.php with a variable cleared is the only way to
     * see the fallback the file actually declares, because env() is read once at
     * boot. The repository is process-global, so whatever is cleared is restored.
     *
     * @var array<string, mixed>
     */
    private array $clearedEnv = [];

    protected function tearDown(): void
    {
        foreach ($this->clearedEnv as $key => $value) {
            if ($value !== null) {
                Env::getRepository()->set($key, (string) $value);
            }
        }

        $this->clearedEnv = [];

        parent::tearDown();
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    private function mailConfigWithout(array $keys): array
    {
        foreach ($keys as $key) {
            $this->clearedEnv[$key] = Env::get($key);
            Env::getRepository()->clear($key);
        }

        return require config_path('mail.php');
    }

    /**
     * Renders a real notification through a real request rather than calling
     * toMail() directly, so the assertions see the message as sent: the envelope
     * the mailer builds out of config, wrapped in the published markdown views.
     */
    private function sentEmail(): Email
    {
        RateLimiter::clear('');
        config(['site.support_email' => 'support@example.test']);

        $this->post('/report', [
            'code_url' => 'https://easy-qr-code.com/q/abc123',
            'reason' => 'phishing',
            'details' => 'It opened a fake bank login page asking for my card details.',
        ])->assertRedirect();

        $transport = app('mailer')->getSymfonyTransport();

        $this->assertInstanceOf(
            ArrayTransport::class,
            $transport,
            'The array mail transport is not active; check MAIL_MAILER in phpunit.xml.',
        );

        $messages = $transport->messages();

        $this->assertNotEmpty($messages, 'The report produced no email to inspect.');

        $email = $messages->first()->getOriginalMessage();

        $this->assertInstanceOf(Email::class, $email);

        return $email;
    }

    private function sentHtml(): string
    {
        return (string) $this->sentEmail()->getHtmlBody();
    }

    public function test_the_from_name_falls_back_to_the_app_name_not_to_example(): void
    {
        $config = $this->mailConfigWithout(['MAIL_FROM_NAME']);

        $this->assertNotSame('Example', $config['from']['name']);
        $this->assertSame(env('APP_NAME'), $config['from']['name']);
    }

    /**
     * Cleared together there is no APP_NAME left to inherit either, which is the
     * state an environment file that was never filled in is in.
     */
    public function test_the_from_name_never_falls_back_to_example(): void
    {
        $config = $this->mailConfigWithout(['MAIL_FROM_NAME', 'APP_NAME']);

        $this->assertNotSame('Example', $config['from']['name']);
        $this->assertNotEmpty($config['from']['name']);
    }

    public function test_the_from_address_falls_back_to_a_domain_we_own(): void
    {
        $config = $this->mailConfigWithout(['MAIL_FROM_ADDRESS']);

        $this->assertStringNotContainsString('example.com', $config['from']['address']);
        $this->assertStringEndsWith('@easy-qr-code.com', $config['from']['address']);
    }

    public function test_the_sent_message_is_signed_with_the_configured_identity(): void
    {
        config([
            'mail.from.address' => 'support@easy-qr-code.com',
            'mail.from.name' => 'Easy QR Code',
        ]);

        $from = $this->sentEmail()->getFrom();

        $this->assertCount(1, $from);
        $this->assertSame('support@easy-qr-code.com', $from[0]->getAddress());
        $this->assertSame('Easy QR Code', $from[0]->getName());
    }

    public function test_our_mail_carries_our_own_logo(): void
    {
        $this->assertStringContainsString(self::LOGO, $this->sentHtml());
    }

    /**
     * A relative src resolves against the mail client's own origin rather than
     * ours, which is the difference between a logo and a broken image.
     */
    public function test_the_logo_url_is_absolute(): void
    {
        $html = $this->sentHtml();

        $this->assertStringContainsString(asset(self::LOGO), $html);
        $this->assertMatchesRegularExpression(
            '~<img[^>]+src="https?://~i',
            $html,
            'The logo src is not an absolute URL.',
        );
    }

    /**
     * Most clients block remote images until the reader allows them, so the alt
     * text is what the majority of recipients read first.
     */
    public function test_the_logo_names_the_brand_when_images_are_blocked(): void
    {
        $this->assertStringContainsString('alt="'.config('app.name').'"', $this->sentHtml());
    }

    /**
     * The stock header swaps in Laravel's hosted logo whenever the app name is
     * "Laravel" and prints the bare name otherwise. Neither is our branding.
     */
    public function test_our_mail_does_not_carry_laravels_logo(): void
    {
        $this->assertStringNotContainsString(
            'laravel.com/img/notification-logo.png',
            $this->sentHtml(),
        );
    }

    /**
     * Outlook drops the stylesheet, so the intrinsic 590x527 would render at
     * full size without dimensions on the element itself.
     */
    public function test_the_logo_is_constrained_for_clients_that_ignore_css(): void
    {
        $html = $this->sentHtml();

        $this->assertMatchesRegularExpression('~<img[^>]+width="63"~', $html);
        $this->assertMatchesRegularExpression('~<img[^>]+height="56"~', $html);
    }

    /**
     * The theme stylesheet is inlined into style attributes at render time. If
     * that stops happening the mail still reads, but every link and button falls
     * back to Laravel's stock palette.
     *
     * Seeds the reported code, because both a button and a body link only appear
     * once the reference resolves — an unmatched report carries neither.
     */
    public function test_the_palette_is_ours_not_laravels(): void
    {
        QrCode::factory()
            ->for(User::factory()->create())
            ->dynamic()
            ->create(['short_url' => 'abc123']);

        $html = $this->sentHtml();

        $this->assertStringContainsString('class="button button-primary"', $html);
        $this->assertStringContainsString('background-color: #348FAD', $html);
        $this->assertStringContainsString('color: #2C7790', $html);

        // Laravel's stock button slate and link blue.
        $this->assertStringNotContainsString('#2d3748', $html);
        $this->assertStringNotContainsString('#3869d4', $html);
    }
}
