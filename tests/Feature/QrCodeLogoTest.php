<?php

namespace Tests\Feature;

use App\Models\QrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;
use Tests\TestCase;

class QrCodeLogoTest extends TestCase
{
    use RefreshDatabase;

    private const CONTENT = 'https://easyqr.link/aB3xK9pQ';

    private const SIZE = 300;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
    }

    /**
     * A solid red rectangle: red appears nowhere in a black-on-white QR code,
     * so the overlay's bounding box can be measured in the rendered PNG.
     */
    private function storeLogo(string $path, int $width, int $height): string
    {
        Storage::put($path, $this->redPng($width, $height));

        return $path;
    }

    private function redPng(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 0, 0));

        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();

        imagedestroy($image);

        return $bytes;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function render(array $overrides = []): string
    {
        return (string) QrCode::buildGenerator(array_merge([
            'format' => 'png',
            'size' => self::SIZE,
            'errorCorrection' => 'M',
        ], $overrides))->generate(self::CONTENT);
    }

    /**
     * @return array{width: int, height: int, count: int}
     */
    private function redBounds(string $png): array
    {
        $image = imagecreatefromstring($png);
        $this->assertNotFalse($image, 'The render is not a decodable image.');

        $minX = PHP_INT_MAX;
        $minY = PHP_INT_MAX;
        $maxX = -1;
        $maxY = -1;
        $count = 0;

        for ($y = 0; $y < imagesy($image); $y++) {
            for ($x = 0; $x < imagesx($image); $x++) {
                $colour = imagecolorat($image, $x, $y);
                $r = ($colour >> 16) & 0xFF;
                $g = ($colour >> 8) & 0xFF;
                $b = $colour & 0xFF;

                if ($r < 200 || $g > 60 || $b > 60) {
                    continue;
                }

                $count++;
                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }

        imagedestroy($image);

        return [
            'width' => $maxX < 0 ? 0 : $maxX - $minX + 1,
            'height' => $maxY < 0 ? 0 : $maxY - $minY + 1,
            'count' => $count,
        ];
    }

    public function test_a_code_without_a_logo_renders_exactly_as_it_did_before(): void
    {
        $untouched = (string) QrCodeGenerator::format('png')
            ->size(self::SIZE)
            ->errorCorrection('M')
            ->color(0, 0, 0)
            ->style('round', 0.5)
            ->generate(self::CONTENT);

        $this->assertSame($untouched, $this->render());
        $this->assertSame($untouched, $this->render(['logo_path' => null]));
        $this->assertSame($untouched, $this->render(['logo_path' => '']));
    }

    public function test_a_logo_changes_the_rendered_image(): void
    {
        $logo = $this->storeLogo('qr-logos/square.png', 200, 200);

        $this->assertNotSame($this->render(), $this->render(['logo_path' => $logo]));
        $this->assertGreaterThan(0, $this->redBounds($this->render(['logo_path' => $logo]))['count']);
    }

    public function test_error_correction_is_forced_high_when_a_logo_is_present(): void
    {
        $logo = $this->storeLogo('qr-logos/square.png', 200, 200);

        $this->assertSame('H', QrCode::effectiveErrorCorrection(['errorCorrection' => 'M', 'logo_path' => $logo]));
        $this->assertSame(
            $this->render(['logo_path' => $logo, 'errorCorrection' => 'H']),
            $this->render(['logo_path' => $logo, 'errorCorrection' => 'M']),
        );
    }

    public function test_the_stored_error_correction_still_applies_without_a_logo(): void
    {
        $this->assertSame('M', QrCode::effectiveErrorCorrection([]));
        $this->assertSame('L', QrCode::effectiveErrorCorrection(['errorCorrection' => 'L']));
        $this->assertNotSame($this->render(['errorCorrection' => 'L']), $this->render(['errorCorrection' => 'H']));
    }

    public function test_the_format_is_forced_to_png_when_a_logo_is_present(): void
    {
        $logo = $this->storeLogo('qr-logos/square.png', 200, 200);

        $this->assertSame('png', QrCode::effectiveFormat(['format' => 'svg', 'logo_path' => $logo]));
        $this->assertSame('svg', QrCode::effectiveFormat(['format' => 'svg']));

        $render = $this->render(['logo_path' => $logo, 'format' => 'svg']);

        $this->assertStringStartsWith("\x89PNG", $render);
    }

    public function test_coverage_is_clamped_to_the_measured_safe_maximum(): void
    {
        $logo = $this->storeLogo('qr-logos/square.png', 200, 200);

        $this->assertSame(
            $this->render(['logo_path' => $logo, 'logo_coverage' => QrCode::LOGO_MAX_COVERAGE]),
            $this->render(['logo_path' => $logo, 'logo_coverage' => 0.9]),
        );

        $bounds = $this->redBounds($this->render(['logo_path' => $logo, 'logo_coverage' => 0.9]));

        $this->assertLessThanOrEqual(
            (int) ceil(self::SIZE * QrCode::LOGO_MAX_COVERAGE) + 2,
            $bounds['width'],
        );
    }

    /**
     * ImageMerge derives the overlay height from the logo's aspect ratio, so a
     * tall logo would otherwise stripe the full height of the symbol.
     */
    public function test_a_tall_logo_is_bounded_by_the_coverage_fraction(): void
    {
        $logo = $this->storeLogo('qr-logos/tall.png', 100, 400);

        $bounds = $this->redBounds($this->render(['logo_path' => $logo, 'logo_coverage' => 0.2]));
        $limit = (int) ceil(self::SIZE * 0.2) + 2;

        $this->assertGreaterThan(0, $bounds['count'], 'The tall logo was not merged at all.');
        $this->assertLessThanOrEqual($limit, $bounds['height'], 'The tall logo striped the symbol.');
        $this->assertLessThanOrEqual($limit, $bounds['width']);
    }

    public function test_a_wide_logo_is_bounded_by_the_coverage_fraction(): void
    {
        $logo = $this->storeLogo('qr-logos/wide.png', 400, 100);

        $bounds = $this->redBounds($this->render(['logo_path' => $logo, 'logo_coverage' => 0.2]));
        $limit = (int) ceil(self::SIZE * 0.2) + 2;

        $this->assertGreaterThan(0, $bounds['count']);
        $this->assertLessThanOrEqual($limit, $bounds['width']);
        $this->assertLessThanOrEqual($limit, $bounds['height']);
    }

    /**
     * Squaring the source unbounded would turn a wide banner into a canvas the
     * size of its longest edge squared.
     */
    public function test_an_oversized_logo_is_scaled_down_before_it_is_squared(): void
    {
        $logo = $this->storeLogo('qr-logos/banner.png', 4000, 100);

        $bounds = $this->redBounds($this->render(['logo_path' => $logo, 'logo_coverage' => 0.2]));
        $limit = (int) ceil(self::SIZE * 0.2) + 2;

        $this->assertGreaterThan(0, $bounds['count']);
        $this->assertLessThanOrEqual($limit, $bounds['width']);
        $this->assertLessThanOrEqual($limit, $bounds['height']);
    }

    public function test_a_logo_smaller_than_the_overlay_is_not_upscaled_by_the_squaring(): void
    {
        $logo = $this->storeLogo('qr-logos/tiny.png', 16, 8);

        $bounds = $this->redBounds($this->render(['logo_path' => $logo, 'logo_coverage' => 0.2]));
        $limit = (int) ceil(self::SIZE * 0.2) + 2;

        $this->assertGreaterThan(0, $bounds['count']);
        $this->assertLessThanOrEqual($limit, $bounds['width']);
        $this->assertLessThanOrEqual($limit, $bounds['height']);
    }

    /**
     * The upload field accepts these three types, so GD has to be able to read
     * all three back — otherwise an accepted upload silently renders no logo.
     */
    public function test_every_accepted_upload_type_can_be_merged(): void
    {
        $encoders = [
            'png' => 'imagepng',
            'jpeg' => 'imagejpeg',
            'webp' => 'imagewebp',
        ];

        foreach ($encoders as $type => $encoder) {
            $this->assertTrue(function_exists($encoder), "GD cannot write {$type}.");

            $image = imagecreatetruecolor(200, 200);
            imagefill($image, 0, 0, imagecolorallocate($image, 255, 0, 0));

            ob_start();
            $encoder($image);
            Storage::put("qr-logos/logo.{$type}", (string) ob_get_clean());

            imagedestroy($image);

            $bounds = $this->redBounds($this->render(['logo_path' => "qr-logos/logo.{$type}"]));

            $this->assertGreaterThan(0, $bounds['count'], "A {$type} logo was not merged.");
        }
    }

    public function test_a_missing_logo_file_renders_a_plain_png_without_throwing(): void
    {
        $render = $this->render(['logo_path' => 'qr-logos/gone.png', 'format' => 'svg']);

        $this->assertStringStartsWith("\x89PNG", $render);
        $this->assertSame(0, $this->redBounds($render)['count']);
    }

    public function test_bytes_gd_cannot_decode_render_a_plain_png_without_throwing(): void
    {
        Storage::put('qr-logos/not-an-image.png', 'this is definitely not a PNG');

        $render = $this->render(['logo_path' => 'qr-logos/not-an-image.png']);

        $this->assertStringStartsWith("\x89PNG", $render);
        $this->assertSame(0, $this->redBounds($render)['count']);
    }

    public function test_an_empty_logo_file_renders_a_plain_png_without_throwing(): void
    {
        Storage::put('qr-logos/empty.png', '');

        $render = $this->render(['logo_path' => 'qr-logos/empty.png']);

        $this->assertStringStartsWith("\x89PNG", $render);
    }

    public function test_a_created_record_stores_the_merged_image_under_a_png_name(): void
    {
        $logo = $this->storeLogo('qr-logos/square.png', 200, 200);

        $qrCode = QrCode::factory()->create([
            'qr_content_data' => ['url' => self::CONTENT],
            'options' => ['format' => 'svg', 'errorCorrection' => 'M', 'size' => self::SIZE, 'logo_path' => $logo],
        ]);

        $this->assertStringEndsWith('.png', $qrCode->qr_code_image);
        $this->assertSame('png', $qrCode->options['format']);
        $this->assertSame('H', $qrCode->options['errorCorrection']);

        $stored = Storage::get($qrCode->qr_code_image);

        $this->assertStringStartsWith("\x89PNG", $stored);
        $this->assertGreaterThan(0, $this->redBounds($stored)['count']);
    }

    public function test_deleting_a_record_removes_its_image_and_its_logo(): void
    {
        $logo = $this->storeLogo('qr-logos/square.png', 200, 200);

        $qrCode = QrCode::factory()->create([
            'qr_content_data' => ['url' => self::CONTENT],
            'options' => ['size' => self::SIZE, 'logo_path' => $logo],
        ]);

        $image = $qrCode->qr_code_image;

        Storage::assertExists($image);
        Storage::assertExists($logo);

        $qrCode->delete();

        Storage::assertMissing($image);
        Storage::assertMissing($logo);
    }

    public function test_deleting_a_record_without_a_logo_removes_only_its_image(): void
    {
        $qrCode = QrCode::factory()->create([
            'qr_content_data' => ['url' => self::CONTENT],
        ]);

        $image = $qrCode->qr_code_image;

        $qrCode->delete();

        Storage::assertMissing($image);
        $this->assertDatabaseMissing('qr_codes', ['id' => $qrCode->id]);
    }

    public function test_deleting_a_record_whose_files_have_already_gone_does_not_error(): void
    {
        $logo = $this->storeLogo('qr-logos/square.png', 200, 200);

        $qrCode = QrCode::factory()->create([
            'qr_content_data' => ['url' => self::CONTENT],
            'options' => ['size' => self::SIZE, 'logo_path' => $logo],
        ]);

        Storage::delete([$qrCode->qr_code_image, $logo]);

        $qrCode->delete();

        $this->assertDatabaseMissing('qr_codes', ['id' => $qrCode->id]);
    }
}
