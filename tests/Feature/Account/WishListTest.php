<?php

use App\Models\Asset;
use App\Models\AssetFavorite;
use App\Models\User;
use App\Models\WishList;
use App\Models\WishListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a default favorites list when a customer first opens wish lists', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('account.wish-lists.index'))
        ->assertOk();

    $this->assertDatabaseHas('wish_lists', [
        'user_id' => $user->id,
        'name' => 'Favorites',
        'is_default' => true,
    ]);
});

it('keeps existing favorite routes mapped to the default list', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $asset = Asset::factory()->discoverable()->create();

    $this->actingAs($user)
        ->post(route('assets.favorite', $asset))
        ->assertRedirect();

    $list = WishList::query()->where('user_id', $user->id)->where('is_default', true)->firstOrFail();

    $this->assertDatabaseHas('wish_list_items', [
        'wish_list_id' => $list->id,
        'asset_id' => $asset->id,
    ]);
    $this->assertDatabaseHas('asset_favorites', [
        'user_id' => $user->id,
        'asset_id' => $asset->id,
    ]);
});

it('allows multiple named wish lists without duplicating the compatibility favorite row', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $asset = Asset::factory()->discoverable()->create();

    $this->actingAs($user)->get(route('account.wish-lists.index'));
    $this->actingAs($user)->post(route('assets.favorite', $asset));

    $this->actingAs($user)
        ->post(route('account.wish-lists.store'), [
            'name' => 'Summer Campaign',
            'description' => 'Ideas for a future campaign.',
            'visibility' => 'private',
        ])
        ->assertRedirect();

    $named = WishList::query()->where('user_id', $user->id)->where('name', 'Summer Campaign')->firstOrFail();

    $this->actingAs($user)
        ->post(route('account.wish-lists.items.store', [$named, $asset]))
        ->assertRedirect();

    expect(WishListItem::query()->where('asset_id', $asset->id)->count())->toBe(2);
    expect(AssetFavorite::query()->where('user_id', $user->id)->where('asset_id', $asset->id)->count())->toBe(1);
    expect($asset->fresh()->favorites_count)->toBe(1);
});

it('does not inflate favorite counts when one customer saves an asset to multiple lists', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $asset = Asset::factory()->discoverable()->create();

    $this->actingAs($user)->get(route('account.wish-lists.index'));
    $default = WishList::query()->where('user_id', $user->id)->where('is_default', true)->firstOrFail();
    $second = WishList::query()->create([
        'user_id' => $user->id,
        'uuid' => fake()->uuid(),
        'name' => 'Second',
        'slug' => 'second',
        'visibility' => 'private',
        'is_default' => false,
    ]);

    $this->actingAs($user)->post(route('account.wish-lists.items.store', [$default, $asset]));
    $this->actingAs($user)->post(route('account.wish-lists.items.store', [$second, $asset]));

    expect($asset->fresh()->favorites_count)->toBe(1);
});

it('prevents customers from accessing another customers private wish list', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $other = User::factory()->create(['email_verified_at' => now()]);
    $list = WishList::query()->create([
        'user_id' => $owner->id,
        'uuid' => fake()->uuid(),
        'name' => 'Private',
        'slug' => 'private',
        'visibility' => 'private',
        'is_default' => false,
    ]);

    $this->actingAs($other)
        ->get(route('account.wish-lists.show', $list))
        ->assertNotFound();
});

it('allows an unlisted wish list to be viewed with its share token', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $list = WishList::query()->create([
        'user_id' => $owner->id,
        'uuid' => fake()->uuid(),
        'name' => 'Shared Ideas',
        'slug' => 'shared-ideas',
        'visibility' => 'unlisted',
        'share_token' => 'safe-test-share-token',
        'is_default' => false,
    ]);

    $this->get(route('shared-wish-lists.show', $list->share_token))
        ->assertOk();
});

it('does not expose a private wish list through an old share token', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $list = WishList::query()->create([
        'user_id' => $owner->id,
        'uuid' => fake()->uuid(),
        'name' => 'Private Ideas',
        'slug' => 'private-ideas',
        'visibility' => 'private',
        'share_token' => null,
        'is_default' => false,
    ]);

    $this->get('/shared/wish-lists/no-longer-valid')
        ->assertNotFound();
});

it('does not allow the default favorites list to be deleted', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($user)->get(route('account.wish-lists.index'));
    $list = WishList::query()->where('user_id', $user->id)->where('is_default', true)->firstOrFail();

    $this->actingAs($user)
        ->delete(route('account.wish-lists.destroy', $list))
        ->assertStatus(422);

    $this->assertDatabaseHas('wish_lists', ['id' => $list->id]);
});
