<?php

namespace Tests\Feature;

use App\Filament\Resources\QrCodeResource\Pages\ViewQrCode;
use App\Models\QrCode;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class QrCodeDownloadTest extends TestCase
{
    use RefreshDatabase;

    private string $diskRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->diskRoot = storage_path('framework/testing/disks/cloud-like');
        File::deleteDirectory($this->diskRoot);
        File::ensureDirectoryExists($this->diskRoot);

        $this->registerCloudLikeDisk();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->diskRoot);

        parent::tearDown();
    }

    /**
     * Stands in for the S3 disk used on Laravel Cloud: reads and writes work,
     * but `path()` returns the bare object key rather than a local filesystem
     * path, exactly as Laravel's S3 driver does.
     */
    private function registerCloudLikeDisk(): void
    {
        Storage::extend('cloud-like', function ($app, array $config): FilesystemAdapter {
            $adapter = new LocalFilesystemAdapter($config['root']);

            return new class(new Flysystem($adapter), $adapter, $config) extends FilesystemAdapter
            {
                public function path($path): string
                {
                    return $path;
                }
            };
        });

        config([
            'filesystems.disks.cloud-like' => [
                'driver' => 'cloud-like',
                'root' => $this->diskRoot,
            ],
            'filesystems.default' => 'cloud-like',
        ]);

        Storage::forgetDisk('cloud-like');
    }

    private function viewPage(QrCode $record): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::actingAs($record->user)->test(ViewQrCode::class, [
            'record' => $record->getKey(),
        ]);
    }

    private function createRecord(): QrCode
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        return QrCode::factory()->for($user)->create(['name' => 'Team Poster']);
    }

    /**
     * @return array<int, string>
     */
    private function zipEntriesFrom(string $zipContents): array
    {
        $path = tempnam(sys_get_temp_dir(), 'qr-zip');
        file_put_contents($path, $zipContents);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true, 'The downloaded file is not a readable zip archive.');

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $entries[$name] = strlen((string) $zip->getFromIndex($i));
        }
        $zip->close();
        @unlink($path);

        foreach ($entries as $name => $size) {
            $this->assertGreaterThan(0, $size, "Zip entry {$name} is empty.");
        }

        return array_keys($entries);
    }

    private function downloadedContents(\Livewire\Features\SupportTesting\Testable $component): string
    {
        $component->assertFileDownloaded();

        return base64_decode((string) data_get($component->effects, 'download.content'));
    }

    public function test_the_stored_image_exists_on_the_cloud_like_disk(): void
    {
        $record = $this->createRecord();

        Storage::assertExists($record->qr_code_image);
        $this->assertFalse(
            file_exists(Storage::path($record->qr_code_image)),
            'The cloud-like disk should not expose a usable local path.',
        );
    }

    public function test_download_all_formats_bundles_every_format_from_cloud_storage(): void
    {
        $record = $this->createRecord();

        $contents = $this->downloadedContents(
            $this->viewPage($record)->callAction('download_all_formats'),
        );

        $entries = $this->zipEntriesFrom($contents);

        sort($entries);
        $this->assertSame([
            'qr-Team Poster.eps',
            'qr-Team Poster.png',
            'qr-Team Poster.svg',
        ], $entries);
    }

    public function test_download_all_formats_also_works_on_a_local_disk(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        $record = $this->createRecord();

        $entries = $this->zipEntriesFrom(
            $this->downloadedContents(
                $this->viewPage($record)->callAction('download_all_formats'),
            ),
        );

        $this->assertCount(3, $entries);
    }

    public function test_download_original_streams_the_stored_image_from_cloud_storage(): void
    {
        $record = $this->createRecord();

        $contents = $this->downloadedContents(
            $this->viewPage($record)->callAction('download_original'),
        );

        $this->assertSame(Storage::get($record->qr_code_image), $contents);
    }
}
