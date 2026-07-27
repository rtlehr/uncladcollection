<?php

namespace App\Services;

use App\Enums\AssetFileRole;
use App\Enums\AssetMediaType;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;

class AssetDiscoveryEligibilityService
{
    public function applyDiscoverable(Builder $query): Builder
    {
        return $query->published()->whereHas('activeFiles', function (Builder $query): void {
            $query->whereIn('role', [
                AssetFileRole::Preview,
                AssetFileRole::Poster,
                AssetFileRole::Thumbnail,
                AssetFileRole::Icon,
                AssetFileRole::Primary,
            ])->whereIn('media_type', [
                AssetMediaType::Image,
                AssetMediaType::Vector,
                AssetMediaType::Video,
            ]);
        });
    }

    public function applyPurchasable(Builder $query): Builder
    {
        return $this->applyDiscoverable($query)
            ->whereHas('offerings', fn (Builder $query) => $query->where('is_active', true));
    }

    public function isDiscoverable(Asset $asset): bool
    {
        return Asset::query()->whereKey($asset->getKey())->tap(fn (Builder $query) => $this->applyDiscoverable($query))->exists();
    }
}
