<?php

use App\Models\NotificationWatchEvent;
use App\Models\User;

it('allows an authorized administrator to preview customer experience maintenance', function () {
    $admin = User::factory()->create();
    $admin->permissions()->create(['name' => 'view_admin', 'label' => 'View admin']);
    $admin->permissions()->create(['name' => 'manage_orders', 'label' => 'Manage orders']);

    NotificationWatchEvent::query()->create(['user_id' => $admin->id, 'event_type' => 'demo', 'fingerprint' => 'old-demo', 'context' => [], 'notified_at' => now()->subYears(2), 'created_at' => now()->subYears(2), 'updated_at' => now()->subYears(2)]);

    $this->actingAs($admin)->get(route('admin.customer-experience.index'))->assertOk();
    $this->actingAs($admin)->post(route('admin.customer-experience.maintain'), ['dry_run' => true])->assertRedirect();
    expect(NotificationWatchEvent::query()->count())->toBe(1);
});
