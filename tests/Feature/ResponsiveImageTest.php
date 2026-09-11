<?php

namespace Tests\Feature;

use App\Support\ResponsiveImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResponsiveImageTest extends TestCase
{
    public function test_project_image_is_stored_as_responsive_webp_and_avif_variants(): void
    {
        Storage::fake('public');

        $result = app(ResponsiveImageService::class)->storeProjectImage(
            UploadedFile::fake()->image('مشروع.png', 1600, 900)
        );

        $this->assertSame(1600, $result['width']);
        $this->assertSame(900, $result['height']);
        $this->assertSame([480, 768, 1200, 1600], collect($result['variants']['webp'])->pluck('width')->all());
        Storage::disk('public')->assertExists(collect($result['variants']['webp'])->pluck('path')->all());

        if (function_exists('imageavif')) {
            $this->assertSame([480, 768, 1200, 1600], collect($result['variants']['avif'])->pluck('width')->all());
            Storage::disk('public')->assertExists(collect($result['variants']['avif'])->pluck('path')->all());
        }
    }
}
