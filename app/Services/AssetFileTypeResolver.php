<?php

namespace App\Services;

use App\Data\ResolvedAssetUpload;
use App\Enums\AssetMediaType;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class AssetFileTypeResolver
{
    public function resolve(UploadedFile $file): ResolvedAssetUpload
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType() ?: $file->getClientMimeType();
        $mediaType = $this->mediaTypeForExtension($extension);

        if (! $mediaType) {
            throw ValidationException::withMessages([
                'files' => "The file extension .{$extension} is not supported.",
            ]);
        }

        return new ResolvedAssetUpload($extension, $mimeType ?: null, $mediaType);
    }

    public function mediaTypeForExtension(string $extension): ?AssetMediaType
    {
        $extension = strtolower(ltrim($extension, '.'));

        foreach ((array) config('asset-media.extensions', []) as $type => $extensions) {
            if (in_array($extension, $extensions, true)) {
                return AssetMediaType::tryFrom($type) ?? AssetMediaType::Other;
            }
        }

        return null;
    }
}
