<?php

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('prefills the registered page key when managing help from a page', function () {
    $viewAdmin = Permission::query()->firstOrCreate(
        ['name' => 'view_admin'],
        ['label' => 'View Admin', 'group_name' => 'Administration'],
    );
    $viewPageHelpAdmin = Permission::query()->firstOrCreate(
        ['name' => 'view_page_help_admin'],
        ['label' => 'View Page Help Admin', 'group_name' => 'Page Help'],
    );
    $manageHelp = Permission::query()->firstOrCreate(
        ['name' => 'manage_page_help'],
        ['label' => 'Manage Page Help', 'group_name' => 'Page Help'],
    );

    $user = User::factory()->create();
    $user->permissions()->attach([$viewAdmin->id, $viewPageHelpAdmin->id, $manageHelp->id]);

    $this->actingAs($user)
        ->get('/admin/page-help/create?page_key=admin.assets.create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/PageHelp/Form')
            ->where('initial_page_key', 'admin.assets.create'));
});

it('does not prefill an unregistered page key', function () {
    $viewAdmin = Permission::query()->firstOrCreate(
        ['name' => 'view_admin'],
        ['label' => 'View Admin', 'group_name' => 'Administration'],
    );
    $viewPageHelpAdmin = Permission::query()->firstOrCreate(
        ['name' => 'view_page_help_admin'],
        ['label' => 'View Page Help Admin', 'group_name' => 'Page Help'],
    );
    $manageHelp = Permission::query()->firstOrCreate(
        ['name' => 'manage_page_help'],
        ['label' => 'Manage Page Help', 'group_name' => 'Page Help'],
    );

    $user = User::factory()->create();
    $user->permissions()->attach([$viewAdmin->id, $viewPageHelpAdmin->id, $manageHelp->id]);

    $this->actingAs($user)
        ->get('/admin/page-help/create?page_key=not.registered')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('initial_page_key', ''));
});
