<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\License;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseBrowseController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $sort = $request->string('sort', 'newest')->toString();

        $licenses = License::query()
            ->with($this->relations())
            ->where('user_id', Auth::id())
            ->where('status', License::STATUS_ACTIVE)
            ->where(function (Builder $query): void {
                $query->whereHas('image')->orWhereHas('asset');
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->whereHas('image', function (Builder $query) use ($search): void {
                            $query->where(function (Builder $query) use ($search): void {
                                $query->where('title', 'like', "%{$search}%")
                                    ->orWhere('description', 'like', "%{$search}%")
                                    ->orWhere('photographer', 'like', "%{$search}%");
                            });
                        })
                        ->orWhereHas('asset', function (Builder $query) use ($search): void {
                            $query->where(function (Builder $query) use ($search): void {
                                $query->where('title', 'like', "%{$search}%")
                                    ->orWhere('description', 'like', "%{$search}%")
                                    ->orWhere('photographer', 'like', "%{$search}%");
                            });
                        });
                });
            })
            ->when($sort === 'oldest', fn (Builder $query) => $query->oldest())
            ->when($sort !== 'oldest', fn (Builder $query) => $query->latest())
            ->paginate(24)
            ->withQueryString()
            ->through(fn (License $license): array => $this->formatCard($license));

        return Inertia::render('Purchases/Index', [
            'licenses' => $licenses,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
            ],
        ]);
    }

    /**
     * Preserve the existing legacy image purchase URL while directing the
     * customer to the license-based detail route used by both purchase types.
     */
    public function show(Image $image): RedirectResponse
    {
        $license = License::query()
            ->where('user_id', Auth::id())
            ->where('image_id', $image->id)
            ->where('status', License::STATUS_ACTIVE)
            ->latest()
            ->first();

        abort_unless($license, 403);

        return redirect()->route('purchases.licenses.show', $license);
    }

    public function showLicense(Request $request, License $license): Response
    {
        abort_unless((int) $license->user_id === (int) $request->user()->id, 403);
        abort_unless($license->status === License::STATUS_ACTIVE, 404);

        $license->load($this->relations());
        abort_unless($license->image || $license->asset, 404);

        return Inertia::render('Purchases/Show', [
            'licenseRecord' => $this->formatDetail($license),
        ]);
    }

    /** @return array<int, string> */
    private function relations(): array
    {
        return [
            'image.collection',
            'image.categories',
            'image.tags',
            'asset.collection',
            'asset.primaryPreviewFile',
            'licenseType',
            'assetOffering.licenseType',
            'order',
            'orderItem',
        ];
    }

    private function formatCard(License $license): array
    {
        $isAsset = $license->asset !== null;
        $product = $isAsset
            ? $this->formatNativeProduct($license)
            : $this->formatLegacyProduct($license);

        return [
            'id' => $license->id,
            'kind' => $isAsset ? 'asset' : 'legacy_image',
            'license_key' => $license->license_key,
            'license_name' => $license->license_name,
            'downloads_used' => $license->downloads_used,
            'download_limit' => $license->download_limit,
            'starts_at' => $license->starts_at?->format('Y-m-d'),
            'expires_at' => $license->expires_at?->format('Y-m-d'),
            'can_download' => ! $isAsset && $license->canDownload(),
            'detail_url' => route('purchases.licenses.show', $license),
            'download_url' => ! $isAsset && $license->image
                ? route('images.download', $license->image)
                : null,
            'quantity' => max(1, (int) ($license->orderItem?->quantity ?? 1)),
            'configuration' => $license->configuration_snapshot,
            'included_files_count' => count($license->included_asset_files_snapshot ?? []),
            'product' => $product,
            'order' => $this->formatOrder($license),
        ];
    }

    private function formatDetail(License $license): array
    {
        $isAsset = $license->asset !== null;
        $product = $isAsset
            ? $this->formatNativeProduct($license, true)
            : $this->formatLegacyProduct($license, true);

        return [
            'id' => $license->id,
            'kind' => $isAsset ? 'asset' : 'legacy_image',
            'license_key' => $license->license_key,
            'license_name' => $license->license_name,
            'license_terms' => $license->license_terms,
            'downloads_used' => $license->downloads_used,
            'download_limit' => $license->download_limit,
            'starts_at' => $license->starts_at?->format('Y-m-d'),
            'expires_at' => $license->expires_at?->format('Y-m-d'),
            'can_download' => ! $isAsset && $license->canDownload(),
            'download_url' => ! $isAsset && $license->image
                ? route('images.download', $license->image)
                : null,
            'quantity' => max(1, (int) ($license->orderItem?->quantity ?? 1)),
            'configuration' => $license->configuration_snapshot,
            'pricing' => $license->pricing_snapshot,
            'included_files' => collect($license->included_asset_files_snapshot ?? [])
                ->map(fn (array $file): array => [
                    'id' => $file['asset_file_id'] ?? null,
                    'name' => $file['original_filename'] ?? 'Included file',
                    'role' => $file['role'] ?? null,
                    'media_type' => $file['media_type'] ?? null,
                    'extension' => isset($file['extension']) ? strtoupper((string) $file['extension']) : null,
                    'mime_type' => $file['mime_type'] ?? null,
                    'size_bytes' => isset($file['size_bytes']) ? (int) $file['size_bytes'] : null,
                ])
                ->values()
                ->all(),
            'product' => $product,
            'order' => $this->formatOrder($license),
        ];
    }

    private function formatNativeProduct(License $license, bool $detailed = false): array
    {
        $asset = $license->asset;
        $preview = $asset?->primaryPreviewFile;

        $product = [
            'id' => $asset->id,
            'title' => $asset->title,
            'slug' => $asset->slug,
            'creator' => $asset->photographer,
            'preview_url' => $preview
                ? route('assets.preview', [$asset, $preview])
                : null,
            'is_ai_generated' => (bool) $asset->is_ai_generated,
            'asset_type_label' => $asset->asset_type->label(),
            'public_url' => route('assets.show', $asset->slug),
        ];

        if (! $detailed) {
            return $product;
        }

        return array_merge($product, [
            'description' => $asset->description,
            'created_at' => $asset->created_at?->format('Y-m-d'),
            'collection' => $asset->collection
                ? ['id' => $asset->collection->id, 'name' => $asset->collection->name]
                : null,
            'categories' => [],
            'tags' => [],
        ]);
    }

    private function formatLegacyProduct(License $license, bool $detailed = false): array
    {
        $image = $license->image;

        $product = [
            'id' => $image->id,
            'title' => $image->title,
            'slug' => $image->slug,
            'creator' => $image->photographer,
            'preview_url' => $image->thumbnail_path
                ? Storage::url($image->thumbnail_path)
                : ($image->icon_path ? Storage::url($image->icon_path) : null),
            'is_ai_generated' => (bool) $image->is_ai_generated,
            'asset_type_label' => 'Image',
            'public_url' => route('images.show', $image->slug),
        ];

        if (! $detailed) {
            return $product;
        }

        return array_merge($product, [
            'description' => $image->description,
            'created_at' => $image->created_at?->format('Y-m-d'),
            'collection' => $image->collection
                ? ['id' => $image->collection->id, 'name' => $image->collection->name]
                : null,
            'categories' => $image->categories
                ->map(fn ($category): array => ['id' => $category->id, 'name' => $category->name])
                ->values()
                ->all(),
            'tags' => $image->tags
                ->map(fn ($tag): array => ['id' => $tag->id, 'name' => $tag->name])
                ->values()
                ->all(),
        ]);
    }

    private function formatOrder(License $license): array
    {
        return [
            'id' => $license->order?->id,
            'order_number' => $license->order?->order_number,
            'paid_at' => $license->order?->paid_at?->format('Y-m-d'),
            'total_formatted' => $license->order?->total_formatted,
            'line_total_formatted' => $license->orderItem?->total_price_formatted,
        ];
    }
}
