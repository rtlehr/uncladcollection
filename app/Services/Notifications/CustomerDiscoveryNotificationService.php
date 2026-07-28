<?php

namespace App\Services\Notifications;

use App\Models\Asset;
use App\Models\User;
use App\Models\UserAssetAffinity;
use App\Models\WishListItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CustomerDiscoveryNotificationService
{
    public function __construct(private readonly CustomerNotificationService $notifications) {}

    /** @return array{checked:int,sent:int,updated:int} */
    public function scanWishLists(?int $userId = null, bool $dryRun = false): array
    {
        $stats = ['checked' => 0, 'sent' => 0, 'updated' => 0];
        $query = WishListItem::query()->with(['wishList.user', 'asset.offerings', 'asset.collection']);
        if ($userId) $query->whereHas('wishList', fn (Builder $q) => $q->where('user_id', $userId));

        $query->orderBy('id')->chunkById(max(25, (int) config('customer-notifications.wish_lists.chunk_size', 200)), function ($items) use (&$stats, $dryRun): void {
            foreach ($items as $item) {
                $stats['checked']++;
                $list = $item->wishList;
                $user = $list?->user;
                $asset = $item->asset;
                if (! $user || ! $asset) continue;

                $currentPrice = $asset->offerings->where('is_active', true)->min('price_cents');
                $currentAvailability = $asset->is_active && $asset->status?->value === 'published' ? 'available' : 'unavailable';
                $currentCollection = $asset->collection_id;

                if ($list->notify_price_changes && $item->price_snapshot_cents !== null && $currentPrice !== null && $currentPrice !== $item->price_snapshot_cents) {
                    $difference = abs($currentPrice - $item->price_snapshot_cents);
                    $percent = $item->price_snapshot_cents > 0 ? ($difference / $item->price_snapshot_cents) * 100 : 100;
                    if ($difference >= config('customer-notifications.wish_lists.price_change_minimum_cents', 100)
                        && $percent >= config('customer-notifications.wish_lists.price_change_minimum_percent', 5)) {
                        $direction = $currentPrice < $item->price_snapshot_cents ? 'dropped' : 'changed';
                        $fingerprint = hash('sha256', "{$item->id}:price:{$currentPrice}");
                        if ($dryRun || $this->notifications->sendOnce($user, 'wish_list_price', $fingerprint, 'wish_lists',
                            "Price {$direction}: {$asset->title}",
                            'A saved asset is now '.'$'.number_format($currentPrice / 100, 2).'.',
                            route('assets.show', $asset), 'View asset', ['old_price_cents' => $item->price_snapshot_cents, 'new_price_cents' => $currentPrice], $asset, $list)) $stats['sent']++;
                    }
                }

                if ($list->notify_availability_changes && $item->availability_snapshot && $currentAvailability !== $item->availability_snapshot) {
                    $fingerprint = hash('sha256', "{$item->id}:availability:{$currentAvailability}");
                    $title = $currentAvailability === 'available' ? 'A saved asset is available again' : 'A saved asset is no longer available';
                    if ($dryRun || $this->notifications->sendOnce($user, 'wish_list_availability', $fingerprint, 'wish_lists', $title,
                        $asset->title.' changed availability.', route('account.wish-lists.show', $list), 'View wish list', ['availability' => $currentAvailability], $asset, $list)) $stats['sent']++;
                }

                $oldCollection = data_get($item->getRawOriginal('availability_snapshot') ? [] : [], 'collection_id');
                $storedCollection = data_get($item->note ? [] : [], 'collection_id');
                if ($list->notify_collection_changes && $currentCollection && $currentCollection !== $storedCollection) {
                    $fingerprint = hash('sha256', "{$item->id}:collection:{$currentCollection}");
                    if ($dryRun || $this->notifications->sendOnce($user, 'wish_list_collection', $fingerprint, 'wish_lists',
                        'A saved asset joined a collection',
                        $asset->title.' is now part of '.($asset->collection?->name ?? 'a curated collection').'.',
                        route('assets.show', $asset), 'View asset', ['collection_id' => $currentCollection], $asset, $list)) $stats['sent']++;
                }

                if (! $dryRun && ($item->price_snapshot_cents !== $currentPrice || $item->availability_snapshot !== $currentAvailability)) {
                    $item->forceFill(['price_snapshot_cents' => $currentPrice, 'availability_snapshot' => $currentAvailability])->saveQuietly();
                    $stats['updated']++;
                }
            }
        });

        return $stats;
    }

    /** @return array{users:int,sent:int} */
    public function sendInterestMatches(?int $userId = null, bool $dryRun = false): array
    {
        $stats = ['users' => 0, 'sent' => 0];
        $users = User::query()->where('is_disabled', false)->whereNotNull('email_verified_at');
        if ($userId) $users->whereKey($userId);

        $users->orderBy('id')->chunkById(100, function ($rows) use (&$stats, $dryRun): void {
            foreach ($rows as $user) {
                $affinities = UserAssetAffinity::query()->where('user_id', $user->id)
                    ->where('score', '>=', config('customer-notifications.interests.minimum_affinity_score', 2))
                    ->orderByDesc('score')->limit(8)->get();
                if ($affinities->isEmpty()) continue;

                $assetIds = $this->matchingAssetIds($affinities);
                if ($assetIds->isEmpty()) continue;
                $stats['users']++;
                $fingerprint = hash('sha256', $user->id.':interest:'.$assetIds->implode(','));
                $count = $assetIds->count();
                if ($dryRun || $this->notifications->sendOnce($user, 'interest_assets', $fingerprint, 'discovery',
                    'New assets selected for you',
                    "We found {$count} new ".($count === 1 ? 'asset' : 'assets').' matching your interests.',
                    route('account.index'), 'See recommendations', ['asset_ids' => $assetIds->all()])) $stats['sent']++;
            }
        });
        return $stats;
    }

    private function matchingAssetIds(Collection $affinities): Collection
    {
        $query = Asset::query()->discoverable()
            ->where('published_at', '>=', now()->subDays(config('customer-notifications.interests.asset_age_days', 7)));

        $query->where(function (Builder $outer) use ($affinities): void {
            foreach ($affinities as $affinity) {
                if ($affinity->dimension === 'category') {
                    $outer->orWhereHas('categories', fn (Builder $q) => $q->where('categories.id', $affinity->value)->orWhere('categories.slug', $affinity->value));
                } elseif ($affinity->dimension === 'tag') {
                    $outer->orWhereHas('tags', fn (Builder $q) => $q->where('tags.id', $affinity->value)->orWhere('tags.slug', $affinity->value));
                } elseif ($affinity->dimension === 'collection') {
                    $outer->orWhereHas('collection', fn (Builder $q) => $q->where('collections.id', $affinity->value)->orWhere('collections.slug', $affinity->value));
                }
            }
        });

        return $query->latest('published_at')->limit(config('customer-notifications.interests.maximum_assets_per_user', 6))->pluck('id');
    }
}
