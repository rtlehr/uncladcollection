<?php

use App\Models\Order;
use App\Models\User;
use App\Models\WishList;

it('shows only the authenticated customers orders', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $other = User::factory()->create(['email_verified_at' => now()]);
    $own = Order::query()->create(['user_id' => $user->id, 'status' => 'paid', 'subtotal_cents' => 1000, 'discount_cents' => 0, 'tax_cents' => 0, 'total_cents' => 1000, 'currency' => 'usd']);
    $foreign = Order::query()->create(['user_id' => $other->id, 'status' => 'paid', 'subtotal_cents' => 1000, 'discount_cents' => 0, 'tax_cents' => 0, 'total_cents' => 1000, 'currency' => 'usd']);

    $this->actingAs($user)->get(route('account.orders.index'))->assertOk()->assertSee($own->order_number)->assertDontSee($foreign->order_number);
    $this->actingAs($user)->get(route('account.orders.show', $foreign))->assertNotFound();
});

it('revokes unlisted wish list links when sharing is disabled', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $list = WishList::query()->create(['user_id' => $user->id, 'name' => 'Shared', 'slug' => 'shared', 'visibility' => 'unlisted', 'share_token' => 'demo-token', 'is_default' => false]);

    $this->actingAs($user)->put(route('account.privacy.update'), [
        'personalized_recommendations' => true,
        'retain_recently_viewed' => true,
        'allow_unlisted_wish_lists' => false,
    ])->assertRedirect();

    expect($list->fresh()->visibility)->toBe('private');
    expect($list->fresh()->share_token)->toBeNull();
});
