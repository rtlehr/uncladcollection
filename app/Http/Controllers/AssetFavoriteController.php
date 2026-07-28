<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Services\AssetDiscoveryEligibilityService;
use App\Services\WishListService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssetFavoriteController extends Controller
{
    public function store(
        Request $request,
        Asset $asset,
        AssetDiscoveryEligibilityService $eligibility,
        WishListService $service,
    ): RedirectResponse {
        abort_unless($eligibility->isDiscoverable($asset), 404);
        $service->add($request->user(), $service->defaultList($request->user()), $asset);

        return back()->with('success', 'Asset added to Favorites.');
    }

    public function destroy(Request $request, Asset $asset, WishListService $service): RedirectResponse
    {
        $service->remove($request->user(), $service->defaultList($request->user()), $asset);

        return back()->with('success', 'Asset removed from Favorites.');
    }
}
