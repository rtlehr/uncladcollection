<?php

use App\Models\Permission;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the public account dashboard to a verified customer', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('account.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Account/Index')
            ->has('summary')
            ->has('recent_licenses')
            ->has('recently_viewed')
            ->has('recommendations'));
});

it('prevents an ordinary customer from entering the internal dashboard', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertForbidden();
});

it('redirects staff from the dashboard alias to the admin command center', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $permission = Permission::query()->firstOrCreate(
        ['name' => 'view_admin'],
        ['label' => 'View admin', 'description' => 'Access administration.'],
    );
    $user->permissions()->attach($permission);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect('/admin');
});

it('keeps old purchase links working through redirects', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get('/purchases')
        ->assertRedirect(route('account.library.index'));
});

it('redirects a customer login to the public account area', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('account.index'));
});
