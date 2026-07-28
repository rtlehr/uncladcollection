<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\WishList;
use App\Models\WishListItem;
use App\Services\AssetDiscoveryEligibilityService;
use App\Services\WishListService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WishListItemController extends Controller
{
    public function store(
        Request $request,
        WishList $wishList,
        Asset $asset,
        WishListService $service,
        AssetDiscoveryEligibilityService $eligibility,
    ): RedirectResponse {
        abort_unless($eligibility->isDiscoverable($asset), 404);
        $service->add($request->user(), $wishList, $asset);

        return back()->with('success', 'Asset saved to '.$wishList->name.'.');
    }

    public function destroy(
        Request $request,
        WishList $wishList,
        Asset $asset,
        WishListService $service,
    ): RedirectResponse {
        $service->remove($request->user(), $wishList, $asset);

        return back()->with('success', 'Asset removed from '.$wishList->name.'.');
    }

    public function move(Request $request, WishListItem $wishListItem, WishListService $service): RedirectResponse
    {
        $validated = $request->validate([
            'wish_list_id' => ['required', 'integer', 'exists:wish_lists,id'],
            'copy' => ['sometimes', 'boolean'],
        ]);

        $destination = WishList::query()->findOrFail($validated['wish_list_id']);
        $service->move($request->user(), $wishListItem, $destination, (bool) ($validated['copy'] ?? false));

        return back()->with('success', ($validated['copy'] ?? false) ? 'Asset copied.' : 'Asset moved.');
    }
}
