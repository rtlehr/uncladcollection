<?php

namespace App\Services;

use App\Data\ResolvedAssetUpload;
use App\Enums\AssetMediaType;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class AssetValidationService
{
    public function __construct(
        private readonly AssetFileTypeResolver $typeResolver,
        private readonly AssetZipInspectionService $zipInspector,
    ) {}

    public function validate(UploadedFile $file): ResolvedAssetUpload
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages(['files' => 'The uploaded file is not valid.']);
        }

        $resolved = $this->typeResolver->resolve($file);
        $extension = $resolved->extension;
        $blocked = array_map('strtolower', (array) config('asset-media.blocked_extensions', []));

        if (in_array($extension, $blocked, true)) {
            throw ValidationException::withMessages(['files' => "Files with the .{$extension} extension are blocked."]);
        }

        $maxKilobytes = (int) config('asset-media.max_upload_kilobytes', 512000);

        if ($file->getSize() !== false && $file->getSize() > ($maxKilobytes * 1024)) {
            throw ValidationException::withMessages(['files' => 'The uploaded file exceeds the configured size limit.']);
        }

        $allowedMimeTypes = (array) config("asset-media.mime_types.{$resolved->mediaType->value}", []);

        if ($allowedMimeTypes !== [] && $resolved->mimeType && ! in_array($resolved->mimeType, $allowedMimeTypes, true)) {
            throw ValidationException::withMessages([
                'files' => "The detected MIME type {$resolved->mimeType} is not allowed for .{$extension} files.",
            ]);
        }

        if ($resolved->mediaType === AssetMediaType::Archive && $extension === 'zip') {
            $this->zipInspector->inspect($file);
        }

        if ($resolved->mediaType === AssetMediaType::Vector && $extension === 'svg') {
            $this->validateSvg($file);
        }

        return $resolved;
    }

    private function validateSvg(UploadedFile $file): void
    {
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw ValidationException::withMessages(['files' => 'The SVG file could not be inspected.']);
        }

        $blockedPatterns = [
            '/<script\b/i',
            '/\bon[a-z]+\s*=/i',
            '/javascript\s*:/i',
            '/<foreignObject\b/i',
            '/(?:href|xlink:href)\s*=\s*["\']\s*(?:https?:|\/\/)/i',
        ];

        foreach ($blockedPatterns as $pattern) {
            if (preg_match($pattern, $contents)) {
                throw ValidationException::withMessages(['files' => 'The SVG contains active or externally referenced content.']);
            }
        }
    }
}
