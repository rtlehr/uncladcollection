<?php

namespace App\Data;

use App\Enums\AssetMediaType;

final readonly class ResolvedAssetUpload
{
    public function __construct(
        public string $extension,
        public ?string $mimeType,
        public AssetMediaType $mediaType,
    ) {}
}
