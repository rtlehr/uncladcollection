<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class BlogImageService
{
    public function storeOriginal(
        UploadedFile $file,
        string $type,
    ): string {
        return $this->storeUnique(
            $file,
            "blog/{$type}/original",
            'source',
        );
    }

    public function storeRendered(
        UploadedFile $file,
        string $type,
    ): string {
        return $this->storeUnique(
            $file,
            "blog/{$type}/rendered",
            $type,
        );
    }

    public function storeContentImage(
        UploadedFile $file,
        string $preset,
    ): string {
        $directory = match ($preset) {
            'blog-content-portrait' => 'blog/content-images/portrait',
            'blog-content-square' => 'blog/content-images/square',
            default => 'blog/content-images/landscape',
        };

        return $this->storeUnique($file, $directory, $preset);
    }

    public function delete(?string $path): void
    {
        if (is_string($path) && $path !== '') {
            Storage::disk('public')->delete($path);
        }
    }

    private function storeUnique(
        UploadedFile $file,
        string $directory,
        string $prefix,
    ): string {
        $extension = strtolower(
            $file->guessExtension()
            ?: $file->getClientOriginalExtension()
            ?: 'jpg',
        );

        $path = $file->storeAs(
            $directory,
            "{$prefix}-".Str::uuid().".{$extension}",
            'public',
        );

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('The blog image could not be stored.');
        }

        return $path;
    }
}
