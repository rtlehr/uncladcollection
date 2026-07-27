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
     * @param array{search:string,category_id:?int,tag_id:?int,collection_id:?int,ai_generated:string,asset_type:string,format:string,orientation:string,min_width:?int,min_height:?int,sort:string} $filters
     */
    public function paginate(array $filters, ?int $userId = null): LengthAwarePaginator
    {
        $query = Asset::query()
            ->discoverable()
            ->leftJoin('asset_search_documents as search_documents', 'search_documents.asset_id', '=', 'assets.id')
            ->select('assets.*')
            ->with([
                'collection:id,name,slug',
                'activeFiles:id,asset_id,role,media_type,disk,directory,stored_filename,original_filename,extension,mime_type,size_bytes,width,height,duration_seconds,page_count,is_downloadable,is_active,sort_order',
                'offerings' => fn ($query) => $query
                    ->where('is_active', true)
                    ->select(['id', 'asset_id', 'name', 'price_cents', 'currency', 'is_active', 'sort_order']),
                'legacyImage:id,title,slug',
                'categories:id,name',
                'tags:id,name',
            ])
            ->when($userId, fn (Builder $query) => $query->with([
                'favorites' => fn ($query) => $query
                    ->where('user_id', $userId)
                    ->select(['id', 'asset_id', 'user_id']),
            ]));

        $search = $this->normalizeSearch($filters['search']);
        $relevanceExpression = null;

        if ($search !== '') {
            $like = "%{$search}%";
            $prefix = "{$search}%";
            $relevanceExpression = '(CASE WHEN search_documents.normalized_title = ? THEN ? ELSE 0 END)'
                .'+(CASE WHEN search_documents.normalized_title LIKE ? THEN ? ELSE 0 END)'
                .'+(CASE WHEN search_documents.normalized_title LIKE ? THEN ? ELSE 0 END)'
                .'+(CASE WHEN search_documents.search_text LIKE ? THEN ? ELSE 0 END)'
                .'+(CASE WHEN assets.is_featured = 1 THEN ? ELSE 0 END)';
            $bindings = [
                $search, config('discovery.search.title_exact_weight', 120),
                $prefix, config('discovery.search.title_prefix_weight', 80),
                $like, config('discovery.search.title_contains_weight', 50),
                $like, config('discovery.search.document_contains_weight', 20),
                config('discovery.search.featured_boost', 6),
            ];

            $query->where(function (Builder $query) use ($like): void {
                $query->where('search_documents.search_text', 'like', $like)
                    ->orWhere('assets.title', 'like', $like)
                    ->orWhere('assets.description', 'like', $like);
            })->selectRaw("{$relevanceExpression} as relevance_score", $bindings);
        }

        $query
            ->when($filters['category_id'], fn (Builder $query, int $id) => $query->whereHas(
                'categories',
                fn (Builder $query) => $query->whereKey($id),
            ))
            ->when($filters['tag_id'], fn (Builder $query, int $id) => $query->whereHas(
                'tags',
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
            ))
            ->when($filters['orientation'] !== '', fn (Builder $query) => $query->where('search_documents.orientation', $filters['orientation']))
            ->when($filters['min_width'], fn (Builder $query, int $width) => $query->where('search_documents.width', '>=', $width))
            ->when($filters['min_height'], fn (Builder $query, int $height) => $query->where('search_documents.height', '>=', $height));

        match ($filters['sort']) {
            'relevance' => $search !== '' && $relevanceExpression !== null
                ? $query->orderByDesc('relevance_score')->orderByDesc('assets.is_featured')->orderByDesc('assets.created_at')
                : $query->latest('assets.created_at'),
            'oldest' => $query->oldest('assets.created_at'),
            'most_viewed' => $query->orderByDesc('assets.views_count'),
            'most_favorited' => $query->orderByDesc('assets.favorites_count'),
            'most_downloaded' => $query->orderByDesc('assets.downloads_count'),
            'trending' => $query
                ->leftJoin('asset_trending_scores as trending_scores', function ($join): void {
                    $join->on('trending_scores.asset_id', '=', 'assets.id')
                        ->where('trending_scores.period', '=', 'week');
                })
                ->orderByRaw('trending_scores.rank is null')
                ->orderBy('trending_scores.rank')
                ->orderByDesc('assets.created_at'),
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
        $highestPrice = $offerings->max('price_cents');
        $currency = $offerings->firstWhere('price_cents', $startingPrice)?->currency ?? 'USD';

        $formatNames = $files
            ->pluck('extension')
            ->filter()
            ->map(fn (string $extension) => strtoupper($extension))
            ->unique()
            ->sort()
            ->values();

        $commerceOfferings = $offerings
            ->map(function ($offering) use ($files): array {
                $includedFiles = $offering->include_all_active_files
                    ? $files->where('is_downloadable', true)
                    : ($offering->relationLoaded('files')
                        ? $offering->files
                            ->where('is_active', true)
                            ->where('is_downloadable', true)
                        : collect());

                return [
                    'id' => $offering->id,
                    'name' => $offering->name,
                    'description' => $offering->description
                        ?: $offering->licenseType?->description,
                    'license_type' => $offering->licenseType ? [
                        'id' => $offering->licenseType->id,
                        'name' => $offering->licenseType->name,
                        'slug' => $offering->licenseType->slug,
                    ] : null,
                    'price_cents' => (int) $offering->price_cents,
                    'currency' => $offering->currency ?: 'USD',
                    'formats' => $includedFiles
                        ->pluck('extension')
                        ->filter()
                        ->map(fn (string $extension) => strtoupper($extension))
                        ->unique()
                        ->sort()
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        $badges = collect()
            ->when($asset->is_featured, fn ($items) => $items->push('Featured'))
            ->when($asset->created_at?->gte(now()->subDays(30)), fn ($items) => $items->push('New'))
            ->when($asset->asset_type === AssetType::Vector, fn ($items) => $items->push('Vector'))
            ->when($asset->asset_type === AssetType::Video, fn ($items) => $items->push('Video'))
            ->when($asset->asset_type === AssetType::Bundle, fn ($items) => $items->push('Bundle'))
            ->when($asset->is_ai_generated, fn ($items) => $items->push('AI Generated'))
            ->when((bool) data_get($asset->metadata, 'editors_choice'), fn ($items) => $items->push("Editor's Choice"))
            ->values()
            ->all();

        return [
            'id' => $asset->id,
            'legacy_image_id' => $asset->legacy_image_id,
            'title' => $asset->title,
            'slug' => $asset->slug,
            'href' => $legacyImage ? route('images.show', $legacyImage) : route('assets.show', $asset),
            'favorite_url' => route('assets.favorite', $asset),
            'unfavorite_url' => route('assets.unfavorite', $asset),
            'photographer' => $asset->photographer,
            'preview_url' => app(AssetPresentationService::class)
                ->marketplaceUrl($asset)
                ?? ($preview
                    ? app(AssetMediaPresentationService::class)->url($asset, $preview)
                    : null),
            'asset_type' => $asset->asset_type->value,
            'asset_type_label' => $asset->asset_type->label(),
            'formats' => $formatNames->all(),
            'starting_price_cents' => $startingPrice !== null ? (int) $startingPrice : null,
            'highest_price_cents' => $highestPrice !== null ? (int) $highestPrice : null,
            'currency' => $currency,
            'offerings_count' => count($commerceOfferings),
            'offerings' => $commerceOfferings,
            'badges' => $badges,
            'license_href' => ($legacyImage ? route('images.show', $legacyImage) : route('assets.show', $asset)).'#purchase',
            'is_ai_generated' => $asset->is_ai_generated,
            'is_featured' => $asset->is_featured,
            'is_favoritable' => true,
            'is_favorited' => $asset->relationLoaded('favorites') && $asset->favorites->isNotEmpty(),
            'favorites_count' => $asset->favorites_count,
            'downloads_count' => $asset->downloads_count,
            'purchases_count' => $asset->purchases_count,
            'views_count' => $asset->views_count,
            'collection' => $asset->collection ? [
                'id' => $asset->collection->id,
                'name' => $asset->collection->name,
                'slug' => $asset->collection->slug,
            ] : null,
            'categories' => $asset->categories
                ->map(fn (Category $category) => ['id' => $category->id, 'name' => $category->name])
                ->values()
                ->all(),
            'tags' => $asset->tags
                ->map(fn (Tag $tag) => ['id' => $tag->id, 'name' => $tag->name])
                ->values()
                ->all(),
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
            ->whereHas('asset', fn (Builder $query) => $query->discoverable())
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
                ->discoverable()
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
                ->discoverable()
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

    private function normalizeSearch(string $search): string
    {
        return \Illuminate\Support\Str::of($search)
            ->lower()
            ->replaceMatches('/[^\pL\pN]+/u', ' ')
            ->squish()
            ->limit(120, '')
            ->toString();
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
