<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetFile;
use App\Commerce\Pricing\DynamicLicensePriceCalculator;
use App\Analytics\AnalyticsTracker;
use App\Enums\AnalyticsEventName;
use App\Enums\DiscoverySource;
use App\Services\AssetMediaPresentationService;
use App\Services\AssetPresentationService;
use App\Services\AssetWatermarkPreviewService;
use App\Services\RecentlyViewedAssetService;
use App\Services\RelatedAssetService;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetBrowseController extends Controller
{
    public function show(
        Request $request,
        Asset $asset,
        AssetMediaPresentationService $presentation,
        RecentlyViewedAssetService $recentlyViewed,
        RelatedAssetService $relatedAssetsService,
        AnalyticsTracker $analytics,
        DynamicLicensePriceCalculator $dynamicPriceCalculator,
    ): Response
    {
        abort_unless(
            $asset->is_active
            && $asset->status->value === 'published'
            && ($asset->published_at === null || $asset->published_at->isPast()),
            404,
        );

        $asset->load([
            'collection:id,name,slug,description',
            'activeFiles',
            'primaryPreviewFile',
            'posterFile',
            'legacyImage:id,slug,title',
            'tags:id,name',
            'configurationGroups' => fn ($query) => $query->where('is_active', true)->with(['values' => fn ($query) => $query->where('is_active', true)->with(['rules' => fn ($query) => $query->where('is_active', true)])]),
            'offerings' => fn ($query) => $query
                ->where('is_active', true)
                ->with(['licenseType:id,name,slug,description,usage_terms,pricing_model,price_cents,image_unit_price_cents,video_unit_price_cents,total_price_cents,minimum_price_cents,currency', 'files', 'pricingTiers' => fn ($query) => $query->where('is_active', true)->orderBy('minimum_quantity')])
                ->orderBy('sort_order')
                ->orderBy('id'),
        ]);

        $files = $asset->activeFiles
            ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
            ->values();

        $gallery = $presentation->gallery($asset, $files);
        $preview = collect($gallery)->firstWhere('id', $asset->primary_preview_file_id)
            ?? collect($gallery)->firstWhere('can_preview', true);

        $source = collect(DiscoverySource::cases())
            ->first(fn (DiscoverySource $case) => $case->value === $request->string('discovery_source')->toString())
            ?->value ?? DiscoverySource::Catalog->value;

        $recentAssets = $recentlyViewed->recent($request, $request->user(), $asset->id);
        $countableView = $recentlyViewed->record($request, $asset, $request->user(), $source);

        if ($countableView) {
            $asset->increment('views_count');
            $asset->views_count++;

            $analytics->record(
                AnalyticsEventName::AssetViewed,
                $asset,
                $request->user(),
                [
                    'discovery_source' => $source,
                    'origin_asset_id' => $request->integer('origin_asset') ?: null,
                ],
                source: $source,
                deduplicationKey: 'asset-view:'.$asset->id,
            );
        }

        $relatedAssets = $relatedAssetsService->forAsset(
            $asset,
            (int) config('discovery.related.limit', 6),
        );

        $recentlyViewedAssets = $recentAssets
            ->map(fn (Asset $recent) => $relatedAssetsService->format(
                $recent,
                'Recently viewed',
                null,
                DiscoverySource::RecentlyViewed->value,
                $asset->id,
            ))
            ->values();

        return Inertia::render('Assets/Show', [
            'asset' => [
                'id' => $asset->id,
                'uuid' => $asset->uuid,
                'title' => $asset->title,
                'slug' => $asset->slug,
                'description' => $asset->description,
                'keywords' => $asset->tags->pluck('name')->filter()->values()->all() ?: ($asset->keywords ?? []),
                'asset_type' => $asset->asset_type->value,
                'asset_type_label' => $asset->asset_type->label(),
                'photographer' => $asset->photographer,
                'is_ai_generated' => $asset->is_ai_generated,
                'allows_quantity' => $asset->allows_quantity,
                'fulfillment_type' => $asset->fulfillment_type->value,
                'collects_shipping_address' => $asset->collects_shipping_address,
                'shipping_address_required' => $asset->shipping_address_required,
                'views_count' => $asset->views_count,
                'downloads_count' => $asset->downloads_count,
                'favorites_count' => $asset->favorites_count,
                'is_favoritable' => true,
                'is_favorited' => Auth::check()
                    ? $asset->favorites()->where('user_id', Auth::id())->exists()
                    : false,
                'favorite_url' => route('assets.favorite', $asset),
                'unfavorite_url' => route('assets.unfavorite', $asset),
                'published_at' => $asset->published_at?->toDateString(),
                'collection' => $asset->collection ? [
                    'id' => $asset->collection->id,
                    'name' => $asset->collection->name,
                    'slug' => $asset->collection->slug,
                ] : null,
                'presentation_url' => app(AssetPresentationService::class)->marketplaceUrl($asset),
                'preview' => $preview,
                'poster' => collect($gallery)->firstWhere('id', $asset->poster_file_id),
                'files' => $gallery,
                'formats' => $files->pluck('extension')->filter()->map(fn (string $extension) => strtoupper($extension))->unique()->values()->all(),
                'legacy_image_url' => $asset->legacyImage ? route('images.show', $asset->legacyImage->slug) : null,
                'configurations' => $asset->configurationGroups->map(fn ($group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'code' => $group->code,
                    'display_type' => $group->display_type->value,
                    'is_required' => $group->is_required,
                    'allows_multiple' => $group->allows_multiple,
                    'placeholder' => $group->placeholder,
                    'help_text' => $group->help_text,
                    'minimum_value' => $group->minimum_value !== null ? (float) $group->minimum_value : null,
                    'maximum_value' => $group->maximum_value !== null ? (float) $group->maximum_value : null,
                    'step_value' => $group->step_value !== null ? (float) $group->step_value : null,
                    'values' => $group->values->map(fn ($value) => [
                        'id' => $value->id,
                        'label' => $value->label,
                        'value' => $value->value,
                        'description' => $value->description,
                        'swatch_color' => $value->swatch_color,
                        'image_url' => $value->image_path ? asset('storage/'.$value->image_path) : null,
                        'is_default' => $value->is_default,
                        'price_adjustment_cents' => (int) ($value->rules->first()?->amount_cents ?? 0),
                        'currency' => $value->rules->first()?->currency ?? 'USD',
                    ])->values(),
                ])->values(),
            ],
            'offerings' => $asset->offerings->map(function ($offering) use ($presentation, $asset, $dynamicPriceCalculator) {
                $included = $offering->includedFiles();
                $pricing = $dynamicPriceCalculator->calculate($offering);

                return [
                    'id' => $offering->id,
                    'name' => $offering->name,
                    'description' => $offering->description,
                    'price_cents' => (int) $pricing['final_price_cents'],
                    'pricing_model' => $pricing['pricing_model'],
                    'currency' => $offering->currency,
                    'download_limit' => $offering->download_limit,
                    'expires_after_days' => $offering->expires_after_days,
                    'include_all_active_files' => $offering->include_all_active_files,
                    'license_type' => [
                        'id' => $offering->licenseType->id,
                        'name' => $offering->licenseType->name,
                        'slug' => $offering->licenseType->slug,
                        'description' => $offering->licenseType->description,
                    ],
                    'files' => $included->map(fn (AssetFile $file) => $presentation->format($asset, $file))->values()->all(),
                    'total_size_bytes' => $included->sum('size_bytes'),
                    'pricing_tiers' => $offering->pricingTiers->map(fn ($tier) => [
                        'id' => $tier->id,
                        'minimum_quantity' => $tier->minimum_quantity,
                        'maximum_quantity' => $tier->maximum_quantity,
                        'pricing_type' => $tier->pricing_type->value,
                        'unit_price_cents' => $tier->unit_price_cents,
                        'percentage_off' => $tier->percentage_off !== null ? (float) $tier->percentage_off : null,
                        'currency' => $tier->currency,
                    ])->values(),
                ];
            })->values(),
            'relatedAssets' => $relatedAssets,
            'recentlyViewedAssets' => $recentlyViewedAssets,
        ]);
    }

    public function preview(Asset $asset, AssetFile $assetFile, AssetMediaPresentationService $presentation): BinaryFileResponse|StreamedResponse|HttpResponse
    {
        abort_unless($assetFile->asset_id === $asset->id && $assetFile->is_active, 404);
        abort_unless($asset->is_active && $asset->status->value === 'published', 404);

        return $presentation->publicResponse($asset, $assetFile);
    }

    public function marketplacePreview(
        Asset $asset,
        AssetWatermarkPreviewService $watermarks,
    ): BinaryFileResponse {
        abort_unless($asset->is_active && $asset->status->value === 'published', 404);

        return $watermarks->marketplaceResponse($asset);
    }
}

