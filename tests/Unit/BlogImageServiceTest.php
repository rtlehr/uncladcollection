<?php

namespace Tests\Unit;

use App\Services\BlogImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogImageServiceTest extends TestCase
{
    public function test_repeated_renders_receive_unique_paths(): void
    {
        Storage::fake('public');

        $service = app(BlogImageService::class);

        $first = $service->storeRendered(
            UploadedFile::fake()->image('one.jpg'),
            'header-images',
        );
        $second = $service->storeRendered(
            UploadedFile::fake()->image('two.jpg'),
            'header-images',
        );

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertExists($first);
        Storage::disk('public')->assertExists($second);
    }
}
