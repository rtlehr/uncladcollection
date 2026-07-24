<?php

use App\Enums\PageHelpAudience;
use App\Models\PageHelp;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PageHelp\PageHelpResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::query()->firstOrCreate(
        ['name' => 'view_admin'],
        [
            'label' => 'View Admin',
            'group_name' => 'Administration',
            'description' => 'Access the administration area.',
            'is_system' => true,
            'is_locked' => true,
        ],
    );
});

it('resolves published public help', function () {
    PageHelp::factory()->create([
        'page_key' => 'public.home',
        'audience' => PageHelpAudience::Public,
    ]);

    expect(app(PageHelpResolver::class)->resolve('public.home', null, 'public'))
        ->toHaveCount(1);
});

it('does not resolve drafts', function () {
    PageHelp::factory()->draft()->create([
        'page_key' => 'public.home',
    ]);

    expect(app(PageHelpResolver::class)->resolve('public.home'))
        ->toHaveCount(0);
});

it('honors custom role targeting', function () {
    $user = User::factory()->create();

    $role = Role::query()->create([
        'name' => 'editor',
        'label' => 'Editor',
    ]);

    $user->roles()->attach($role);

    $help = PageHelp::factory()->create([
        'page_key' => 'admin.assets.index',
        'audience' => PageHelpAudience::Custom,
    ]);

    $help->roles()->attach($role);

    expect(app(PageHelpResolver::class)->resolve('admin.assets.index', $user, 'admin'))
        ->toHaveCount(1);
});

it('protects page help administration', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/page-help')
        ->assertForbidden();
});
