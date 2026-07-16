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
                ->orderBy('sort_order'),
            'legacyImage.categories:id,name',
            'legacyImage.tags:id,name',
        ]);

        return response()->json([
            'asset' => $catalog->formatCard($asset),
        ]);
    }
}
