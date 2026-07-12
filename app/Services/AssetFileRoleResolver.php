<?php

namespace App\Services;

use App\Enums\AssetFileRole;
use App\Enums\AssetMediaType;

class AssetFileRoleResolver
{
    public function resolve(AssetMediaType $mediaType): AssetFileRole
    {
        return match ($mediaType) {
            AssetMediaType::Image => AssetFileRole::Primary,
            AssetMediaType::Vector => AssetFileRole::Vector,
            AssetMediaType::Video => AssetFileRole::Video,
            AssetMediaType::Archive => AssetFileRole::Bundle,
            AssetMediaType::Source => AssetFileRole::Source,
            AssetMediaType::Document,
            AssetMediaType::Other => AssetFileRole::Supplemental,
        };
    }
}
