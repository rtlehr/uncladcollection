<?php

use App\Models\Asset;
use App\Models\AssetOffering;
use App\Models\NotificationWatchEvent;
use App\Models\User;
use App\Models\WishList;
use App\Models\WishListItem;
use App\Services\Notifications\CustomerDiscoveryNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('sends one deduplicated price-change notification and refreshes the snapshot', function () {
    Notification::fake();
    $user = User::factory()->create(['email_verified_at' => now(), 'is_disabled' => false]);
    $asset = Asset::factory()->published()->create(['is_active' => true]);
    AssetOffering::factory()->create(['asset_id' => $asset->id, 'is_active' => true, 'price_cents' => 1500]);
    $list = WishList::query()->create(['user_id' => $user->id, 'name' => 'Favorites', 'slug' => 'favorites', 'visibility' => 'private', 'is_default' => true, 'notify_price_changes' => true]);
    $item = WishListItem::query()->create(['wish_list_id' => $list->id, 'asset_id' => $asset->id, 'price_snapshot_cents' => 2000, 'availability_snapshot' => 'available']);

    $service = app(CustomerDiscoveryNotificationService::class);
    expect($service->scanWishLists($user->id)['sent'])->toBe(1);
    expect($service->scanWishLists($user->id)['sent'])->toBe(0);
    expect($item->fresh()->price_snapshot_cents)->toBe(1500);
    expect(NotificationWatchEvent::query()->count())->toBe(1);
});

it('allows a customer to update notification settings only for their own list', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $other = User::factory()->create(['email_verified_at' => now()]);
    $list = WishList::query()->create(['user_id' => $owner->id, 'name' => 'Ideas', 'slug' => 'ideas', 'visibility' => 'private']);

    $this->actingAs($owner)->patch(route('account.wish-lists.notifications', $list), [
        'notify_price_changes' => true,
        'notify_availability_changes' => false,
        'notify_collection_changes' => true,
    ])->assertRedirect();

    expect($list->fresh()->notify_price_changes)->toBeTrue();
    $this->actingAs($other)->patch(route('account.wish-lists.notifications', $list), [
        'notify_price_changes' => false,
        'notify_availability_changes' => false,
        'notify_collection_changes' => false,
    ])->assertNotFound();
});
