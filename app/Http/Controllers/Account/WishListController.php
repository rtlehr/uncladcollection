<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\WishList;
use App\Services\PublicAssetCatalogService;
use App\Services\WishListService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WishListController extends Controller
{
    public function legacyRedirect(Request $request, WishListService $service): RedirectResponse
    {
        return redirect()->route('account.wish-lists.show', $service->defaultList($request->user()));
    }

    public function index(Request $request, WishListService $service): Response
    {
        $default = $service->defaultList($request->user());

        $lists = WishList::query()
            ->forUser($request->user()->id)
            ->withCount('items')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (WishList $list) => $this->summary($list));

        return Inertia::render('Account/WishLists/Index', [
            'lists' => $lists,
            'default_list_id' => $default->id,
        ]);
    }

    public function store(Request $request, WishListService $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['required', Rule::in(['private', 'unlisted'])],
        ]);

        $list = $service->createList($request->user(), $validated);

        return redirect()->route('account.wish-lists.show', $list)
            ->with('success', 'Wish list created.');
    }

    public function show(
        Request $request,
        WishList $wishList,
        PublicAssetCatalogService $catalog,
        WishListService $service,
    ): Response {
        $service->guardOwner($request->user(), $wishList);

        $items = $wishList->items()
            ->with([
                'asset.collection:id,name,slug',
                'asset.categories:id,name',
                'asset.tags:id,name',
                'asset.activeFiles',
                'asset.offerings' => fn ($query) => $query->where('is_active', true),
                'asset.legacyImage:id,title,slug',
                'asset.favorites' => fn ($query) => $query->where('user_id', $request->user()->id),
            ])
            ->paginate(24)
            ->withQueryString();

        $items->through(function ($item) use ($catalog): array {
            return [
                'id' => $item->id,
                'note' => $item->note,
                'added_at' => $item->created_at?->toIso8601String(),
                'asset' => $catalog->formatCard($item->asset),
            ];
        });

        return Inertia::render('Account/WishLists/Show', [
            'wish_list' => $this->summary($wishList->loadCount('items')),
            'items' => $items,
            'lists' => WishList::query()
                ->forUser($request->user()->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'is_default']),
        ]);
    }

    public function update(Request $request, WishList $wishList, WishListService $service): RedirectResponse
    {
        $service->guardOwner($request->user(), $wishList);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $wishList->update($validated);

        return back()->with('success', 'Wish list updated.');
    }

    public function updateSharing(Request $request, WishList $wishList, WishListService $service): RedirectResponse
    {
        $validated = $request->validate([
            'visibility' => ['required', Rule::in(['private', 'unlisted'])],
        ]);

        $service->updateSharing($request->user(), $wishList, $validated['visibility']);

        return back()->with('success', $validated['visibility'] === 'unlisted'
            ? 'Share link enabled.'
            : 'Wish list is private.');
    }


    public function updateNotifications(Request $request, WishList $wishList, WishListService $service): RedirectResponse
    {
        $service->guardOwner($request->user(), $wishList);
        $validated = $request->validate([
            'notify_price_changes' => ['required', 'boolean'],
            'notify_availability_changes' => ['required', 'boolean'],
            'notify_collection_changes' => ['required', 'boolean'],
        ]);
        $wishList->update($validated);
        return back()->with('success', 'Wish-list notification settings updated.');
    }

    public function destroy(Request $request, WishList $wishList, WishListService $service): RedirectResponse
    {
        $service->delete($request->user(), $wishList);

        return redirect()->route('account.wish-lists.index')
            ->with('success', 'Wish list deleted.');
    }

    private function summary(WishList $list): array
    {
        return [
            'id' => $list->id,
            'uuid' => $list->uuid,
            'name' => $list->name,
            'slug' => $list->slug,
            'description' => $list->description,
            'visibility' => $list->visibility,
            'is_default' => $list->is_default,
            'notify_price_changes' => $list->notify_price_changes,
            'notify_availability_changes' => $list->notify_availability_changes,
            'notify_collection_changes' => $list->notify_collection_changes,
            'items_count' => (int) ($list->items_count ?? 0),
            'share_url' => $list->isShareable()
                ? route('shared-wish-lists.show', $list->share_token)
                : null,
            'last_activity_at' => $list->last_activity_at?->toIso8601String(),
        ];
    }
}
