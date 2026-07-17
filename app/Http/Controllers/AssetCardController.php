<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Services\PublicAssetCatalogService;
use Illuminate\Http\JsonResponse;

class AssetCardController extends Controller
{
    public function show(
        Asset $asset,
        PublicAssetCatalogService $catalog,
    ): JsonResponse {
        abort_unless(
            $asset->is_active && $asset->status->value === 'published',
            404,
        );

        $asset->load([
            'collection:id,name,slug',
            'activeFiles',
            'offerings' => fn ($query) => $query
                ->where('is_active', true)
                ->with([
                    'licenseType:id,name,slug,description',
                    'files:id,asset_id,extension,is_active,is_downloadable,sort_order',
                ])
                ->orderBy('sort_order'),
            'legacyImage.categories:id,name',
            'legacyImage.tags:id,name',
        ]);

        return response()->json([
            'asset' => $catalog->formatCard($asset),
        ]);
    }
}
