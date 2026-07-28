<?php

namespace App\Http\Controllers;

use App\Models\AssetFile;
use App\Models\Image;
use App\Models\License;
use App\Services\Downloads\DownloadEntitlementService;
use App\Services\Licenses\LicenseStatusService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseBrowseController extends Controller
{
    public function __construct(
        private readonly DownloadEntitlementService $downloadEntitlements,
        private readonly LicenseStatusService $statuses,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());
        $sort = $request->string('sort', 'newest')->toString();
        $status = $request->string('status', 'all')->toString();

        $base = License::query()
            ->where('user_id', Auth::id())
            ->where(fn (Builder $query) => $query->whereHas('image')->orWhereHas('asset'));

        $statusCounts = [
            'all' => (clone $base)->count(),
            'active' => (clone $base)->where('status', License::STATUS_ACTIVE)
                ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()))->count(),
            'expiring_soon' => (clone $base)->where('status', License::STATUS_ACTIVE)
                ->whereBetween('expires_at', [now(), now()->addDays(30)])->count(),
            'expired' => (clone $base)->where(fn (Builder $query) => $query
                ->where('status', License::STATUS_EXPIRED)
                ->orWhere(fn (Builder $query) => $query->where('status', License::STATUS_ACTIVE)->where('expires_at', '<', now())))->count(),
            'revoked' => (clone $base)->where('status', License::STATUS_REVOKED)->count(),
            'refunded' => (clone $base)->where('status', License::STATUS_REFUNDED)->count(),
        ];

        $licenses = $base->with($this->relations())
            ->when($status === 'active', fn (Builder $query) => $query->where('status', License::STATUS_ACTIVE)
                ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', now())))
            ->when($status === 'expiring_soon', fn (Builder $query) => $query->where('status', License::STATUS_ACTIVE)
                ->whereBetween('expires_at', [now(), now()->addDays(30)]))
            ->when($status === 'expired', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('status', License::STATUS_EXPIRED)
                ->orWhere(fn (Builder $query) => $query->where('status', License::STATUS_ACTIVE)->where('expires_at', '<', now()))))
            ->when(in_array($status, [License::STATUS_REVOKED, License::STATUS_REFUNDED], true), fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('license_key', 'like', "%{$search}%")
                        ->orWhere('license_name', 'like', "%{$search}%")
                        ->orWhereHas('order', fn (Builder $query) => $query->where('order_number', 'like', "%{$search}%"))
                        ->orWhereHas('image', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('asset', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"));
                });
            })
            ->when($sort === 'oldest', fn (Builder $query) => $query->oldest())
            ->when($sort !== 'oldest', fn (Builder $query) => $query->latest())
            ->paginate(24)->withQueryString()
            ->through(fn (License $license): array => $this->formatCard($license));

        return Inertia::render('Purchases/Index', [
            'licenses' => $licenses,
            'filters' => ['search' => $search, 'sort' => $sort, 'status' => $status],
            'statusCounts' => $statusCounts,
        ]);
    }

    public function show(Image $image): RedirectResponse
    {
        $license = License::query()->where('user_id', Auth::id())->where('image_id', $image->id)->latest()->first();
        abort_unless($license, 403);
        return redirect()->route('account.licenses.show', $license);
    }

    public function showLicense(Request $request, License $license): Response
    {
        abort_unless((int) $license->user_id === (int) $request->user()->id, 403);
        $license->load(array_merge($this->relations(), ['downloads.assetFile', 'statusHistories.changedBy']));
        abort_unless($license->image || $license->asset, 404);

        return Inertia::render('Purchases/Show', ['licenseRecord' => $this->formatDetail($license, $request)]);
    }

    /** @return array<int, string> */
    private function relations(): array
    {
        return [
            'image.collection', 'image.categories', 'image.tags',
            'asset.collection', 'asset.primaryPreviewFile',
            'licenseType', 'assetOffering.licenseType', 'order', 'orderItem',
        ];
    }

    private function formatCard(License $license): array
    {
        $isAsset = $license->asset !== null;
        $status = $this->statuses->describe($license);
        $product = $isAsset ? $this->formatNativeProduct($license) : $this->formatLegacyProduct($license);

        return [
            'id' => $license->id,
            'kind' => $isAsset ? 'asset' : 'legacy_image',
            'license_key' => $license->license_key,
            'license_name' => $license->license_name,
            'status' => $status,
            'downloads_used' => $license->downloads_used,
            'download_limit' => $license->download_limit,
            'starts_at' => $license->starts_at?->format('Y-m-d'),
            'expires_at' => $license->expires_at?->format('Y-m-d'),
            'can_download' => $status['can_download'] && ! $isAsset && $license->canDownload(),
            'detail_url' => route('account.licenses.show', $license),
            'download_url' => $status['can_download'] && ! $isAsset && $license->image ? route('images.download', $license->image) : null,
            'quantity' => max(1, (int) ($license->orderItem?->quantity ?? 1)),
            'configuration' => $license->configuration_snapshot,
            'included_files_count' => count($license->included_asset_files_snapshot ?? []),
            'product' => $product,
            'order' => $this->formatOrder($license),
        ];
    }

    private function formatDetail(License $license, Request $request): array
    {
        $isAsset = $license->asset !== null;
        $status = $this->statuses->describe($license);
        $product = $isAsset ? $this->formatNativeProduct($license, true) : $this->formatLegacyProduct($license, true);
        $availableAssetFiles = $isAsset && $status['can_download'] && $license->canDownload()
            ? $this->downloadEntitlements->availableFiles($request->user(), $license) : collect();
        $currentTerms = $license->licenseType?->usage_terms;
        $termsChanged = filled($currentTerms) && trim((string) $currentTerms) !== trim((string) $license->license_terms);

        return [
            'id' => $license->id,
            'kind' => $isAsset ? 'asset' : 'legacy_image',
            'license_key' => $license->license_key,
            'license_name' => $license->license_name,
            'license_terms' => $license->license_terms,
            'current_license_terms' => $termsChanged ? $currentTerms : null,
            'terms_changed' => $termsChanged,
            'terms_version' => $license->terms_version,
            'status' => $status,
            'status_reason' => $license->status_reason,
            'downloads_used' => $license->downloads_used,
            'download_limit' => $license->download_limit,
            'starts_at' => $license->starts_at?->format('Y-m-d'),
            'expires_at' => $license->expires_at?->format('Y-m-d'),
            'can_download' => $status['can_download'] && $license->canDownload() && (! $isAsset || $availableAssetFiles->isNotEmpty()),
            'download_url' => ! $isAsset && $status['can_download'] && $license->image ? route('images.download', $license->image)
                : ($isAsset && $availableAssetFiles->isNotEmpty() ? route('account.licenses.download-all', $license) : null),
            'download_all_url' => $isAsset && $availableAssetFiles->isNotEmpty() ? route('account.licenses.download-all', $license) : null,
            'certificate_url' => route('account.licenses.documents.certificate', $license),
            'proof_of_purchase_url' => route('account.licenses.documents.proof', $license),
            'support_url' => '/support?license_id='.$license->id.'#submit-request',
            'quantity' => max(1, (int) ($license->orderItem?->quantity ?? 1)),
            'configuration' => $license->configuration_snapshot,
            'pricing' => $license->pricing_snapshot,
            'included_files' => collect($license->included_asset_files_snapshot ?? [])->map(function (array $file) use ($license, $availableAssetFiles): array {
                $available = $availableAssetFiles->first(fn (AssetFile $candidate): bool =>
                    ((int) ($file['asset_file_id'] ?? 0) > 0 && (int) $file['asset_file_id'] === (int) $candidate->id)
                    || (! empty($file['uuid']) && (string) $file['uuid'] === (string) $candidate->uuid));
                return [
                    'id' => $file['asset_file_id'] ?? null,
                    'name' => $file['original_filename'] ?? 'Included file',
                    'role' => $file['role'] ?? null,
                    'media_type' => $file['media_type'] ?? null,
                    'extension' => isset($file['extension']) ? strtoupper((string) $file['extension']) : null,
                    'mime_type' => $file['mime_type'] ?? null,
                    'size_bytes' => isset($file['size_bytes']) ? (int) $file['size_bytes'] : null,
                    'is_available' => $available !== null,
                    'download_url' => $available ? route('account.licenses.files.download', [$license, $available]) : null,
                ];
            })->values()->all(),
            'download_history' => $license->downloads->sortByDesc('downloaded_at')->map(fn ($download): array => [
                'id' => $download->id,
                'type' => $download->download_type,
                'filename' => $download->original_filename ?: $download->assetFile?->original_filename,
                'status' => $download->status ?: 'completed',
                'downloaded_at' => $download->downloaded_at?->format('Y-m-d H:i'),
            ])->values()->all(),
            'status_history' => $license->statusHistories->map(fn ($history): array => [
                'id' => $history->id,
                'from_status' => $history->from_status,
                'to_status' => $history->to_status,
                'message' => $history->customer_message ?: $history->reason,
                'changed_at' => $history->created_at?->format('Y-m-d H:i'),
            ])->values()->all(),
            'product' => $product,
            'order' => $this->formatOrder($license),
        ];
    }

    private function formatNativeProduct(License $license, bool $detailed = false): array
    {
        $asset = $license->asset;
        $preview = $asset?->primaryPreviewFile;
        $product = [
            'id' => $asset->id, 'title' => $asset->title, 'slug' => $asset->slug,
            'creator' => $asset->photographer,
            'preview_url' => $preview ? route('assets.preview', [$asset, $preview]) : null,
            'is_ai_generated' => (bool) $asset->is_ai_generated,
            'asset_type_label' => $asset->asset_type->label(),
            'public_url' => route('assets.show', $asset->slug),
        ];
        return ! $detailed ? $product : array_merge($product, [
            'description' => $asset->description, 'created_at' => $asset->created_at?->format('Y-m-d'),
            'collection' => $asset->collection ? ['id' => $asset->collection->id, 'name' => $asset->collection->name] : null,
            'categories' => [], 'tags' => [],
        ]);
    }

    private function formatLegacyProduct(License $license, bool $detailed = false): array
    {
        $image = $license->image;
        $product = [
            'id' => $image->id, 'title' => $image->title, 'slug' => $image->slug,
            'creator' => $image->photographer,
            'preview_url' => $image->thumbnail_path ? Storage::url($image->thumbnail_path) : ($image->icon_path ? Storage::url($image->icon_path) : null),
            'is_ai_generated' => (bool) $image->is_ai_generated,
            'asset_type_label' => 'Image', 'public_url' => route('images.show', $image->slug),
        ];
        return ! $detailed ? $product : array_merge($product, [
            'description' => $image->description, 'created_at' => $image->created_at?->format('Y-m-d'),
            'collection' => $image->collection ? ['id' => $image->collection->id, 'name' => $image->collection->name] : null,
            'categories' => $image->categories->map(fn ($category): array => ['id' => $category->id, 'name' => $category->name])->values()->all(),
            'tags' => $image->tags->map(fn ($tag): array => ['id' => $tag->id, 'name' => $tag->name])->values()->all(),
        ]);
    }

    private function formatOrder(License $license): array
    {
        return [
            'id' => $license->order?->id,
            'order_number' => $license->order?->order_number,
            'status' => $license->order?->status,
            'paid_at' => $license->order?->paid_at?->format('Y-m-d'),
            'total_formatted' => $license->order?->total_formatted,
            'line_total_formatted' => $license->orderItem?->total_price_formatted,
        ];
    }
}
