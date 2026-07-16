<?php

namespace App\Services;

use App\Enums\AssetFileRole;
use App\Enums\AssetMediaType;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Tag;
use App\Services\AssetPresentationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;

class PublicAssetCatalogService
{
    /**
     * @param array{search:string,category_id:?int,tag_id:?int,collection_id:?int,ai_generated:string,asset_type:string,format:string,sort:string} $filters
     */
    public function paginate(array $filters, ?int $userId = null): LengthAwarePaginator
    {
        $query = Asset::query()
            ->published()
            ->with([
                'collection:id,name,slug',
                'activeFiles:id,asset_id,role,media_type,disk,directory,stored_filename,original_filename,extension,mime_type,size_bytes,width,height,duration_seconds,page_count,is_downloadable,is_active,sort_order',
                'offerings' => fn ($query) => $query
                    ->where('is_active', true)
                    ->select(['id', 'asset_id', 'name', 'price_cents', 'currency', 'is_active', 'sort_order']),
                'legacyImage:id,title,slug',
                'legacyImage.categories:id,name',
                'legacyImage.tags:id,name',
            ])
            ->when($userId, fn (Builder $query) => $query->with([
                'legacyImage.favorites' => fn ($query) => $query
                    ->where('user_id', $userId)
                    ->select(['id', 'image_id', 'user_id']),
            ]));

        $search = $filters['search'];

        $query
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = "%{$search}%";
                $query->where(function (Builder $query) use ($like): void {
                    $query
                        ->where('assets.title', 'like', $like)
                        ->orWhere('assets.description', 'like', $like)
                        ->orWhere('assets.photographer', 'like', $like)
                        ->orWhereHas('collection', fn (Builder $query) => $query->where('name', 'like', $like))
                        ->orWhereHas('activeFiles', function (Builder $query) use ($like): void {
                            $query->where('original_filename', 'like', $like)
                                ->orWhere('extension', 'like', $like);
                        })
                        ->orWhereHas('legacyImage.categories', fn (Builder $query) => $query->where('name', 'like', $like))
                        ->orWhereHas('legacyImage.tags', fn (Builder $query) => $query->where('name', 'like', $like));
                });
            })
            ->when($filters['category_id'], fn (Builder $query, int $id) => $query->whereHas(
                'legacyImage.categories',
                fn (Builder $query) => $query->whereKey($id),
            ))
            ->when($filters['tag_id'], fn (Builder $query, int $id) => $query->whereHas(
                'legacyImage.tags',
                fn (Builder $query) => $query->whereKey($id),
            ))
            ->when($filters['collection_id'], fn (Builder $query, int $id) => $query->where('collection_id', $id))
            ->when(
                in_array($filters['ai_generated'], ['0', '1'], true),
                fn (Builder $query) => $query->where('is_ai_generated', $filters['ai_generated'] === '1'),
            )
            ->when($filters['asset_type'] !== '', fn (Builder $query) => $query->where('asset_type', $filters['asset_type']))
            ->when($filters['format'] !== '', fn (Builder $query) => $query->whereHas(
                'activeFiles',
                fn (Builder $query) => $query->where('extension', strtolower($filters['format'])),
            ));

        match ($filters['sort']) {
            'oldest' => $query->oldest('assets.created_at'),
            'most_viewed' => $query->orderByDesc('assets.views_count'),
            'most_favorited' => $query->orderByDesc('assets.favorites_count'),
            'most_downloaded' => $query->orderByDesc('assets.downloads_count'),
            default => $query->latest('assets.created_at'),
        };

        return $query
            ->paginate(24)
            ->withQueryString()
            ->through(fn (Asset $asset) => $this->formatCard($asset));
    }

    public function formatCard(Asset $asset): array
    {
        $files = $asset->activeFiles;
        $preview = $this->resolvePreviewFile($asset, $files);
        $legacyImage = $asset->legacyImage;
        $offerings = $asset->offerings;
        $startingPrice = $offerings->min('price_cents');
        $currency = $offerings->firstWhere('price_cents', $startingPrice)?->currency ?? 'USD';

        return [
            'id' => $asset->id,
            'legacy_image_id' => $asset->legacy_image_id,
            'title' => $asset->title,
            'slug' => $asset->slug,
            'href' => $legacyImage ? route('images.show', $legacyImage) : route('assets.show', $asset),
            'favorite_url' => $legacyImage ? route('images.favorite', $legacyImage) : null,
            'unfavorite_url' => $legacyImage ? route('images.unfavorite', $legacyImage) : null,
            'photographer' => $asset->photographer,
            'preview_url' => app(AssetPresentationService::class)
                ->marketplaceUrl($asset)
                ?? ($preview
                    ? ($preview->publicUrl() ?? route('assets.preview', [$asset, $preview]))
                    : null),
            'asset_type' => $asset->asset_type->value,
            'asset_type_label' => $asset->asset_type->label(),
            'formats' => $files
                ->pluck('extension')
                ->filter()
                ->map(fn (string $extension) => strtoupper($extension))
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'starting_price_cents' => $startingPrice !== null ? (int) $startingPrice : null,
            'currency' => $currency,
            'is_ai_generated' => $asset->is_ai_generated,
            'is_featured' => $asset->is_featured,
            'is_favoritable' => $legacyImage !== null,
            'is_favorited' => (bool) ($legacyImage?->favorites?->isNotEmpty()),
            'favorites_count' => $asset->favorites_count,
            'downloads_count' => $asset->downloads_count,
            'purchases_count' => $asset->purchases_count,
            'views_count' => $asset->views_count,
            'collection' => $asset->collection ? [
                'id' => $asset->collection->id,
                'name' => $asset->collection->name,
                'slug' => $asset->collection->slug,
            ] : null,
            'categories' => $legacyImage?->categories
                ?->map(fn (Category $category) => ['id' => $category->id, 'name' => $category->name])
                ->values()
                ->all() ?? [],
            'tags' => $legacyImage?->tags
                ?->map(fn (Tag $tag) => ['id' => $tag->id, 'name' => $tag->name])
                ->values()
                ->all() ?? [],
        ];
    }

    public function assetTypeOptions(): array
    {
        return collect(AssetType::cases())
            ->map(fn (AssetType $type) => ['value' => $type->value, 'label' => $type->label()])
            ->values()
            ->all();
    }

    public function formatOptions(): array
    {
        return AssetFile::query()
            ->where('is_active', true)
            ->whereHas('asset', fn (Builder $query) => $query->published())
            ->whereNotNull('extension')
            ->select('extension')
            ->distinct()
            ->orderBy('extension')
            ->pluck('extension')
            ->filter()
            ->map(fn (string $extension) => [
                'value' => strtolower($extension),
                'label' => strtoupper($extension),
            ])
            ->values()
            ->all();
    }

    public function suggestions(string $search): array
    {
        if (mb_strlen($search) < 2) {
            return [];
        }

        $like = "%{$search}%";

        return collect()
            ->concat(Collection::query()
                ->where('is_active', true)
                ->where('name', 'like', $like)
                ->limit(3)
                ->get(['id', 'name', 'slug'])
                ->map(fn (Collection $item) => [
                    'type' => 'collection',
                    'label' => $item->name,
                    'value' => $item->name,
                    'href' => "/collections/{$item->slug}",
                    'meta' => 'Collection',
                ]))
            ->concat(Asset::query()
                ->published()
                ->where('title', 'like', $like)
                ->limit(4)
                ->get(['title', 'slug', 'legacy_image_id'])
                ->map(fn (Asset $item) => [
                    'type' => 'asset',
                    'label' => $item->title,
                    'value' => $item->title,
                    'href' => $item->legacy_image_id ? "/images/{$item->slug}" : "/assets/{$item->slug}",
                    'meta' => 'Asset',
                ]))
            ->concat(Asset::query()
                ->published()
                ->whereNotNull('photographer')
                ->where('photographer', 'like', $like)
                ->select('photographer')
                ->distinct()
                ->limit(3)
                ->get()
                ->map(fn (Asset $item) => [
                    'type' => 'creator',
                    'label' => $item->photographer,
                    'value' => $item->photographer,
                    'href' => null,
                    'meta' => 'Creator',
                ]))
            ->unique(fn (array $item) => $item['type'].'|'.$item['label'])
            ->take(8)
            ->values()
            ->all();
    }

    private function resolvePreviewFile(Asset $asset, SupportCollection $files): ?AssetFile
    {
        $explicit = $asset->primaryPreviewFile;

        if ($explicit && $explicit->is_active) {
            return $explicit;
        }

        return $files->first(fn (AssetFile $file) => in_array($file->role, [
            AssetFileRole::Preview,
            AssetFileRole::Poster,
            AssetFileRole::Thumbnail,
            AssetFileRole::Icon,
            AssetFileRole::Primary,
        ], true) && in_array($file->media_type, [
            AssetMediaType::Image,
            AssetMediaType::Vector,
        ], true));
    }
}
