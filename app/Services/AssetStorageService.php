<?php

namespace App\Services;

use App\Enums\AssetFileRole;
use App\Models\Asset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;

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

        return hash_file('sha256', $path);
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
