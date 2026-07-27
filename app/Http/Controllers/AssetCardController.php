<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Services\AssetDiscoveryEligibilityService;
use App\Services\PublicAssetCatalogService;
use Illuminate\Http\JsonResponse;

class AssetCardController extends Controller
{
    public function show(
        Asset $asset,
        PublicAssetCatalogService $catalog,
        AssetDiscoveryEligibilityService $eligibility,
    ): JsonResponse {
        abort_unless(
            $eligibility->isDiscoverable($asset),
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
            'categories:id,name',
            'tags:id,name',
        ]);

        return response()->json([
            'asset' => $catalog->formatCard($asset),
        ]);
    }
}
