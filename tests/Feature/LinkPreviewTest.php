<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The card WhatsApp, Slack and X build when someone shares a link to us.
 *
 * This is the first thing most people ever see of the product, and it is
 * invisible from inside the app: nothing in our own UI breaks when the tags are
 * wrong. It went unnoticed for months that there were no og: tags at all, so
 * WhatsApp fell back to the apple-touch-icon and every shared link carried the
 * Bootstrap starter template's logo.
 */
class LinkPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_offers_a_large_image_preview(): void
    {
        $response = $this->get(route('welcome'));

        $response->assertOk()
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<meta property="og:image" content="'.asset(config('site.share.image')).'">', false)
            ->assertSee('<meta property="og:image:width" content="1200">', false)
            ->assertSee('<meta property="og:image:height" content="630">', false)
            ->assertSee('<meta property="og:type" content="website">', false);
    }

    /**
     * A relative og:image is silently dropped by most crawlers, which is the
     * same failure as having none at all.
     */
    public function test_the_preview_image_url_is_absolute(): void
    {
        $this->get(route('welcome'))
            ->assertSee('<meta property="og:image" content="'.config('app.url'), false);
    }

    public function test_the_preview_image_exists_at_the_advertised_size(): void
    {
        $path = public_path(config('site.share.image'));

        $this->assertFileExists($path, 'The og:image points at a file that is not in the repo.');

        [$width, $height] = getimagesize($path);

        $this->assertSame(config('site.share.image_width'), $width, 'og:image:width does not match the file.');
        $this->assertSame(config('site.share.image_height'), $height, 'og:image:height does not match the file.');
    }

    /**
     * The preview follows the page, so a page that has bothered to write its own
     * description does not get the homepage's.
     */
    public function test_a_page_preview_carries_that_page_description(): void
    {
        $this->get(route('pricing'))
            ->assertSee('<meta property="og:description" content="Static QR codes are free forever.', false)
            ->assertDontSee('<meta property="og:description" content="Create free static QR codes instantly', false);
    }

    /**
     * Rendered bare, because every page we ship today writes its own
     * description. The fallback is what a page added tomorrow will get.
     */
    public function test_pages_without_their_own_description_fall_back_to_the_shared_one(): void
    {
        $this->startSession();

        $html = view('layouts.site')->render();

        $this->assertStringContainsString('<meta property="og:description" content="'.e(config('site.share.description')).'">', $html);
        $this->assertStringContainsString('<meta name="description" content="'.e(config('site.share.description')).'">', $html);
    }

    /**
     * The icon iOS uses for a home screen bookmark, and what WhatsApp reaches for
     * if og:image ever goes missing again. It shipped as the purple Bootstrap "B"
     * from the starter template; this asserts we are not serving someone else's
     * brand from our own domain.
     */
    public function test_the_apple_touch_icon_is_ours(): void
    {
        $path = public_path('landing/assets/img/apple-touch-icon.png');

        $this->assertFileExists($path);

        [$width, $height] = getimagesize($path);
        $this->assertSame(180, $width);
        $this->assertSame(180, $height);

        $this->assertLessThan(
            0.1,
            $this->purpleShare($path),
            'The apple-touch-icon is mostly purple, which is what the Bootstrap template icon looked like.'
        );
    }

    /**
     * The fraction of pixels that are unmistakably purple. Our mark is black and
     * white, so anything above a rounding error means the template icon is back.
     */
    private function purpleShare(string $path): float
    {
        $image = imagecreatefrompng($path);
        $width = imagesx($image);
        $height = imagesy($image);
        $purple = 0;

        for ($x = 0; $x < $width; $x += 4) {
            for ($y = 0; $y < $height; $y += 4) {
                ['red' => $red, 'green' => $green, 'blue' => $blue] = imagecolorsforindex(
                    $image,
                    imagecolorat($image, $x, $y)
                );

                if ($blue > 120 && $blue > $green + 60 && $red > $green + 40) {
                    $purple++;
                }
            }
        }

        return $purple / (ceil($width / 4) * ceil($height / 4));
    }
}
