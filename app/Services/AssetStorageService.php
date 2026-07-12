<?php

namespace App\Services;

use App\Enums\AssetFileRole;
use App\Models\Asset;
use App\Models\AssetFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class AssetStorageService
{
    public function disk(): string
    {
        return (string) config('asset-media.private_disk', 'asset-files');
    }

    public function directory(Asset $asset, AssetFileRole $role): string
    {
        $date = $asset->created_at ?? now();

        return sprintf(
            'assets/%s/%s/%s/%s',
            $date->format('Y'),
            $date->format('m'),
            $asset->uuid,
            $this->roleDirectory($role),
        );
    }

    public function store(Asset $asset, UploadedFile $file, AssetFileRole $role): array
    {
        $disk = $this->disk();
        $directory = $this->directory($asset, $role);
        $storedFilename = $this->randomFilename($file);
        $path = trim($directory.'/'.$storedFilename, '/');
        $checksum = $this->checksum($file);

        $stream = fopen($file->getRealPath(), 'rb');

        if (! is_resource($stream)) {
            throw new RuntimeException('The uploaded file could not be opened for storage.');
        }

        try {
            $stored = Storage::disk($disk)->put($path, $stream, [
                'visibility' => 'private',
            ]);
        } finally {
            fclose($stream);
        }

        if (! $stored) {
            throw new RuntimeException('The uploaded file could not be stored.');
        }

        return [
            'disk' => $disk,
            'directory' => $directory,
            'stored_filename' => $storedFilename,
            'path' => $path,
            'checksum_sha256' => $checksum,
        ];
    }

    public function delete(AssetFile $assetFile): bool
    {
        return $this->deleteStoredPath($assetFile->disk, $assetFile->path);
    }

    public function deleteStoredPath(string $disk, string $path): bool
    {
        if (! Storage::disk($disk)->exists($path)) {
            return true;
        }

        return Storage::disk($disk)->delete($path);
    }

    public function randomFilename(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === '') {
            throw new InvalidArgumentException('The uploaded file must have an approved extension.');
        }

        return Str::ulid()->toBase32().'.'.$extension;
    }

    public function checksum(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        if ($path === false) {
            throw new InvalidArgumentException('The uploaded file could not be read.');
        }

        $checksum = hash_file('sha256', $path);

        if ($checksum === false) {
            throw new RuntimeException('The uploaded file checksum could not be calculated.');
        }

        return $checksum;
    }

    private function roleDirectory(AssetFileRole $role): string
    {
        return match ($role) {
            AssetFileRole::Preview,
            AssetFileRole::Thumbnail,
            AssetFileRole::Icon,
            AssetFileRole::Poster => 'preview',
            AssetFileRole::Primary,
            AssetFileRole::HighResolution,
            AssetFileRole::Print => 'image',
            AssetFileRole::Vector => 'vector',
            AssetFileRole::Video => 'video',
            AssetFileRole::Source => 'source',
            AssetFileRole::Bundle => 'bundle',
            AssetFileRole::Supplemental => 'supplemental',
        };
    }
}
