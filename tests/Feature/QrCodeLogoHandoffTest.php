<?php

namespace Tests\Feature;

use App\Filament\Public\Resources\QrCodeResource\Pages\CreateQrCode as PublicCreateQrCode;
use App\Filament\Resources\QrCodeResource\Pages\CreateFromSession;
use App\Models\QrCode;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The public form and the record that eventually holds the logo are separated by
 * a registration, and the appearance settings have to survive the gap. They did
 * not: `pending_qr_code` carried four keys and `options` was not among them, so
 * every choice made on the public form was replaced by defaults and an uploaded
 * logo was left on disk with nothing pointing at it.
 */
class QrCodeLogoHandoffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
    }

    private function redPng(int $width = 200, int $height = 200): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 0, 0));

        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();

        imagedestroy($image);

        return $bytes;
    }

    private function countsRedPixels(string $png): int
    {
        $image = imagecreatefromstring($png);
        $this->assertNotFalse($image, 'The render is not a decodable image.');

        $count = 0;

        for ($y = 0; $y < imagesy($image); $y++) {
            for ($x = 0; $x < imagesx($image); $x++) {
                $colour = imagecolorat($image, $x, $y);

                if ((($colour >> 16) & 0xFF) >= 200 && (($colour >> 8) & 0xFF) <= 60 && ($colour & 0xFF) <= 60) {
                    $count++;
                }
            }
        }

        imagedestroy($image);

        return $count;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function submitPublicForm(array $options): void
    {
        Filament::setCurrentPanel(Filament::getPanel('public'));

        Livewire::test(PublicCreateQrCode::class)
            ->fillForm([
                'name' => 'Guest code',
                'type' => 'static',
                'qr_content_type' => 'website',
                'qr_content_data' => ['url' => 'https://example.com'],
                'options' => $options,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    private function completeRegistration(): QrCode
    {
        $user = User::factory()->create();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::actingAs($user)->test(CreateFromSession::class);

        return $user->qrCodes()->sole();
    }

    public function test_a_logo_chosen_as_a_guest_survives_registration(): void
    {
        $this->submitPublicForm([
            'logo_path' => UploadedFile::fake()->createWithContent('logo.png', $this->redPng()),
            'size' => 600,
        ]);

        $qrCode = $this->completeRegistration();

        $this->assertNotEmpty($qrCode->options['logo_path'] ?? null, 'The logo path did not survive the handoff.');
        $this->assertTrue(Storage::exists($qrCode->options['logo_path']));
        $this->assertGreaterThan(
            0,
            $this->countsRedPixels(Storage::get($qrCode->qr_code_image)),
            'The rendered code carries no logo.',
        );
    }

    /**
     * The logo made this visible, but style and colour had been discarded the
     * same way since the appearance settings landed.
     */
    public function test_the_other_appearance_choices_survive_registration_too(): void
    {
        $this->submitPublicForm([
            'style' => 'dot',
            'color' => '#ff0000',
            'size' => 400,
            'format' => 'svg',
            'errorCorrection' => 'Q',
        ]);

        $qrCode = $this->completeRegistration();

        $this->assertSame('dot', $qrCode->options['style']);
        $this->assertSame('#ff0000', $qrCode->options['color']);
        $this->assertSame(400, (int) $qrCode->options['size']);
        $this->assertSame('svg', $qrCode->options['format']);
        $this->assertSame('Q', $qrCode->options['errorCorrection']);
    }

    /**
     * A guest who chooses nothing must still get exactly what they got before
     * `options` began travelling with the rest of the payload.
     */
    public function test_a_guest_who_chooses_nothing_still_gets_a_working_code(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('public'));

        Livewire::test(PublicCreateQrCode::class)
            ->fillForm([
                'name' => 'Plain code',
                'type' => 'static',
                'qr_content_type' => 'website',
                'qr_content_data' => ['url' => 'https://example.com'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $qrCode = $this->completeRegistration();

        $this->assertNull($qrCode->options['logo_path'] ?? null);
        $this->assertTrue(Storage::exists($qrCode->qr_code_image));
    }

    public function test_an_abandoned_upload_is_pruned_once_the_grace_period_has_passed(): void
    {
        $this->submitPublicForm([
            'logo_path' => UploadedFile::fake()->createWithContent('logo.png', $this->redPng()),
        ]);

        $abandoned = Storage::files('qr-logos');
        $this->assertCount(1, $abandoned, 'The public form did not store an upload to abandon.');

        Session::forget('pending_qr_code');
        $this->travel(49)->hours();

        $this->artisan('logos:prune')->assertSuccessful();

        $this->assertSame([], Storage::files('qr-logos'));
    }

    /**
     * The window exists for exactly this case: the file is unreferenced because
     * the record does not exist yet, not because nobody wants it.
     */
    public function test_an_upload_inside_the_grace_period_is_left_alone(): void
    {
        $this->submitPublicForm([
            'logo_path' => UploadedFile::fake()->createWithContent('logo.png', $this->redPng()),
        ]);

        $this->travel(47)->hours();

        $this->artisan('logos:prune')->assertSuccessful();

        $this->assertCount(1, Storage::files('qr-logos'));
    }

    public function test_a_logo_a_record_still_refers_to_is_never_pruned(): void
    {
        $this->submitPublicForm([
            'logo_path' => UploadedFile::fake()->createWithContent('logo.png', $this->redPng()),
        ]);

        $qrCode = $this->completeRegistration();

        $this->travel(100)->hours();

        $this->artisan('logos:prune')->assertSuccessful();

        $this->assertTrue(Storage::exists($qrCode->options['logo_path']));
    }

    public function test_a_dry_run_reports_without_deleting(): void
    {
        $this->submitPublicForm([
            'logo_path' => UploadedFile::fake()->createWithContent('logo.png', $this->redPng()),
        ]);

        Session::forget('pending_qr_code');
        $this->travel(49)->hours();

        $this->artisan('logos:prune', ['--dry-run' => true])->assertSuccessful();

        $this->assertCount(1, Storage::files('qr-logos'));
    }

    public function test_a_grace_period_below_an_hour_refuses_to_prune(): void
    {
        $this->submitPublicForm([
            'logo_path' => UploadedFile::fake()->createWithContent('logo.png', $this->redPng()),
        ]);

        $this->travel(1000)->hours();
        config(['site.orphan_logo_grace_hours' => 0]);

        $this->artisan('logos:prune')->assertFailed();

        $this->assertCount(1, Storage::files('qr-logos'));
    }
}
