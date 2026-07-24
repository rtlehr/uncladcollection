<?php

use App\Models\Permission;
use App\Models\User;
use App\Services\PageHelp\PageHelpContext;
use App\Services\PageHelp\PageHelpRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

uses(RefreshDatabase::class);

function pageHelpCoverageRequest(
    string $routeName,
    ?User $user = null,
    array $middleware = [],
    string $uri = '/coverage-test',
): Request {
    $request = Request::create($uri, 'GET');
    $route = new Route(['GET'], ltrim($uri, '/'), fn () => null);
    $route->name($routeName);
    $route->middleware($middleware);
    $request->setRouteResolver(fn () => $route);
    $request->setUserResolver(fn () => $user);

    return $request;
}

it('generates a page help key for an unregistered authenticated page', function () {
    $page = app(PageHelpRegistry::class)->forRoute(
        'account.notifications.index',
        ['web', 'auth', 'verified'],
        'account/notifications',
    );

    expect($page)
        ->not->toBeNull()
        ->and($page['key'])->toBe('member.account.notifications.index')
        ->and($page['area'])->toBe('Member')
        ->and($page['generated'])->toBeTrue();
});

it('shows the help management control on an unregistered page for help administrators', function () {
    $permission = Permission::query()->firstOrCreate(
        ['name' => 'manage_page_help'],
        ['label' => 'Manage Page Help', 'group_name' => 'Page Help'],
    );

    $user = User::factory()->create();
    $user->permissions()->attach($permission);

    $payload = app(PageHelpContext::class)->forRequest(
        pageHelpCoverageRequest(
            'account.notifications.index',
            $user,
            ['web', 'auth', 'verified'],
            '/account/notifications',
        ),
    );

    expect($payload)
        ->not->toBeNull()
        ->and($payload['key'])->toBe('member.account.notifications.index')
        ->and($payload['entries'])->toBe([])
        ->and($payload['can_manage'])->toBeTrue()
        ->and($payload['manage_url'])->toContain(
            'page_key=member.account.notifications.index',
        );
});

it('keeps an undocumented generated page hidden from users without published help', function () {
    $payload = app(PageHelpContext::class)->forRequest(
        pageHelpCoverageRequest(
            'account.notifications.index',
            null,
            ['web'],
            '/account/notifications',
        ),
    );

    expect($payload)->toBeNull();
});

it('keeps configured page keys as friendly overrides', function () {
    $page = app(PageHelpRegistry::class)->forRoute('home');

    expect($page)
        ->not->toBeNull()
        ->and($page['key'])->toBe('public.home')
        ->and($page['name'])->toBe('Public Home')
        ->and($page)->not->toHaveKey('generated');
});
