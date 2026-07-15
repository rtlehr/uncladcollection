<?php

namespace Tests\Unit\Uploads;

use App\Support\Uploads\UploadStreamResolver;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Tests\TestCase;

class UploadStreamResolverTest extends TestCase
{
    public function test_it_reads_laravel_fake_file_created_with_reported_size(): void
    {
        $file = UploadedFile::fake()->create(
            'source.zip',
            250,
            'application/zip',
        );

        $contents = $this->contents($file);

        // Laravel reports 250 KB while the framework-owned tmpfile may contain
        // zero physical bytes. The important behavior is that it remains a
        // valid readable stream and does not throw.
        $this->assertSame('', $contents);
        $this->assertSame(250 * 1024, $file->getSize());
    }

    public function test_it_reads_laravel_fake_file_created_with_content(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'source.zip',
            'archive bytes',
        );

        $this->assertSame('archive bytes', $this->contents($file));
    }

    public function test_it_reads_laravel_fake_image(): void
    {
        $file = UploadedFile::fake()->image(
            'preview.jpg',
            1200,
            800,
        );

        $this->assertNotSame('', $this->contents($file));
    }

    public function test_it_reads_a_path_backed_uploaded_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'uc-upload-');

        file_put_contents($path, 'browser upload bytes');

        try {
            $file = new UploadedFile(
                $path,
                'document.pdf',
                'application/pdf',
                null,
                true,
            );

            $this->assertSame(
                'browser upload bytes',
                $this->contents($file),
            );
        } finally {
            @unlink($path);
        }
    }

    private function contents(UploadedFile $file): string
    {
        $stream = app(UploadStreamResolver::class)->open($file);

        try {
            return stream_get_contents($stream);
        } finally {
            fclose($stream);
        }
    }
}
