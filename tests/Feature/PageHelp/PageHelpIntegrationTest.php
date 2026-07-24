<?php

use App\Enums\PageHelpAudience;
use App\Models\PageHelp;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PageHelp\PageHelpContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

uses(RefreshDatabase::class);

function pageHelpRequest(string $routeName, ?User $user = null): Request
{
    $request = Request::create('/test', 'GET');
    $route = new Route(['GET'], '/test', fn () => null);
    $route->name($routeName);
    $request->setRouteResolver(fn () => $route);
    $request->setUserResolver(fn () => $user);

    return $request;
}

beforeEach(function () {
    Permission::query()->firstOrCreate(
        ['name' => 'view_admin'],
        ['label' => 'View Admin', 'group_name' => 'Administration'],
    );
});

it('maps the current route to visible page help', function () {
    PageHelp::factory()->create([
        'page_key' => 'public.home',
        'title' => 'Welcome help',
        'audience' => PageHelpAudience::Public,
    ]);

    $payload = app(PageHelpContext::class)->forRequest(pageHelpRequest('home'));

    expect($payload)
        ->not->toBeNull()
        ->and($payload['key'])->toBe('public.home')
        ->and($payload['entries'])->toHaveCount(1)
        ->and($payload['entries'][0]['title'])->toBe('Welcome help');
});

it('does not expose help for an unregistered route', function () {
    expect(app(PageHelpContext::class)->forRequest(pageHelpRequest('unknown.route')))
        ->toBeNull();
});

it('returns an empty management state for page help administrators', function () {
    $permission = Permission::query()->firstOrCreate(
        ['name' => 'manage_page_help'],
        ['label' => 'Manage Page Help', 'group_name' => 'Page Help'],
    );
    $role = Role::query()->create(['name' => 'administrator', 'label' => 'Administrator']);
    $role->permissions()->attach($permission);
    $user = User::factory()->create();
    $user->roles()->attach($role);

    $payload = app(PageHelpContext::class)->forRequest(pageHelpRequest('admin.assets.index', $user));

    expect($payload)
        ->not->toBeNull()
        ->and($payload['entries'])->toBe([])
        ->and($payload['can_manage'])->toBeTrue()
        ->and($payload['manage_url'])->not->toBeNull();
});

it('keeps audience-restricted content out of the shared payload', function () {
    PageHelp::factory()->create([
        'page_key' => 'public.home',
        'audience' => PageHelpAudience::Admin,
    ]);

    expect(app(PageHelpContext::class)->forRequest(pageHelpRequest('home')))
        ->toBeNull();
});
