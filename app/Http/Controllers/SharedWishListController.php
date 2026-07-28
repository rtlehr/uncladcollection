<?php

namespace App\Http\Controllers;

use App\Analytics\AnalyticsTracker;
use App\Enums\AnalyticsEventName;
use App\Models\WishList;
use App\Services\PublicAssetCatalogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SharedWishListController extends Controller
{
    public function __invoke(
        Request $request,
        string $token,
        PublicAssetCatalogService $catalog,
        AnalyticsTracker $tracker,
    ): Response {
        $wishList = WishList::query()
            ->where('visibility', 'unlisted')
            ->where('share_token', $token)
            ->with('user:id,name,username')
            ->firstOrFail();

        $items = $wishList->items()
            ->whereHas('asset', fn ($query) => $query->discoverable())
            ->with([
                'asset.collection:id,name,slug',
                'asset.categories:id,name',
                'asset.tags:id,name',
                'asset.activeFiles',
                'asset.offerings' => fn ($query) => $query->where('is_active', true),
                'asset.legacyImage:id,title,slug',
            ])
            ->paginate(24)
            ->withQueryString();

        $items->through(fn ($item) => [
            'id' => $item->id,
            'note' => $item->note,
            'asset' => $catalog->formatCard($item->asset),
        ]);

        $tracker->record(
            AnalyticsEventName::WishListSharedViewed,
            subject: $wishList,
            user: $request->user(),
            dimensions: ['owner_user_id' => $wishList->user_id],
            source: 'shared_wish_list',
            channel: 'onsite',
        );

        return Inertia::render('WishLists/Shared', [
            'wish_list' => [
                'name' => $wishList->name,
                'description' => $wishList->description,
                'owner_name' => $wishList->user?->username ?: $wishList->user?->name,
                'items_count' => $items->total(),
            ],
            'items' => $items,
        ]);
    }
}
