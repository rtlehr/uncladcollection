<?php

namespace App\Services\PageHelp;

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class PageHelpRegistry
{
    public function all(): array
    {
        $configured = collect(config('page-help.registry', []))
            ->map(fn (array $value, string $key) => array_merge(['key' => $key], $value));

        $configuredRouteNames = $configured
            ->pluck('route')
            ->filter()
            ->values();

        $discovered = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $route) => $this->isPageRoute($route))
            ->reject(fn (RoutingRoute $route) => $configuredRouteNames->contains($route->getName()))
            ->map(fn (RoutingRoute $route) => $this->fallback(
                (string) $route->getName(),
                $route->gatherMiddleware(),
                $route->uri(),
            ));

        return $configured
            ->concat($discovered)
            ->unique('key')
            ->sortBy([
                ['area', 'asc'],
                ['name', 'asc'],
            ])
            ->values()
            ->all();
    }

    public function keys(): array
    {
        return collect($this->all())->pluck('key')->all();
    }

    public function get(string $key): ?array
    {
        $configured = config("page-help.registry.{$key}");

        if (is_array($configured)) {
            return array_merge(['key' => $key], $configured);
        }

        return collect($this->all())
            ->first(fn (array $page) => $page['key'] === $key);
    }

    public function forRoute(
        ?string $routeName,
        array $middleware = [],
        ?string $uri = null,
    ): ?array {
        if (! $routeName) {
            return null;
        }

        $configured = collect(config('page-help.registry', []))
            ->map(fn (array $value, string $key) => array_merge(['key' => $key], $value))
            ->first(fn (array $page) => ($page['route'] ?? null) === $routeName);

        return $configured ?: $this->fallback($routeName, $middleware, $uri);
    }

    private function fallback(
        string $routeName,
        array $middleware = [],
        ?string $uri = null,
    ): array {
        $area = $this->inferArea($routeName, $middleware, $uri);
        $areaKey = strtolower($area);

        $key = Str::startsWith($routeName, "{$areaKey}.")
            ? $routeName
            : "{$areaKey}.{$routeName}";

        return [
            'key' => $key,
            'name' => $this->friendlyName($routeName),
            'area' => $area,
            'route' => $routeName,
            'generated' => true,
        ];
    }

    private function inferArea(
        string $routeName,
        array $middleware,
        ?string $uri,
    ): string {
        $uri = ltrim((string) $uri, '/');
        $middleware = collect($middleware)->map(fn ($value) => (string) $value);

        if (Str::startsWith($routeName, 'admin.') || Str::startsWith($uri, 'admin/')) {
            return 'Admin';
        }

        if (Str::startsWith($routeName, 'advertiser.') || Str::startsWith($uri, 'advertiser/')) {
            return 'Advertiser';
        }

        if (
            $middleware->contains(fn (string $value) =>
                $value === 'auth'
                || Str::startsWith($value, 'auth:')
                || $value === 'verified'
                || Str::startsWith($value, 'permission:')
            )
        ) {
            return 'Member';
        }

        return 'Public';
    }

    private function friendlyName(string $routeName): string
    {
        $segments = collect(explode('.', $routeName))
            ->reject(fn (string $segment) => in_array($segment, [
                'admin',
                'advertiser',
                'public',
                'member',
                'index',
                'show',
            ], true))
            ->values();

        $name = $segments
            ->map(fn (string $segment) => Str::headline($segment))
            ->implode(' ');

        return $name !== '' ? $name : Str::headline($routeName);
    }

    private function isPageRoute(RoutingRoute $route): bool
    {
        $name = $route->getName();

        if (! is_string($name) || $name === '') {
            return false;
        }

        if (! in_array('GET', $route->methods(), true)) {
            return false;
        }

        return ! Str::endsWith($name, [
            '.download',
            '.export',
            '.attachment',
            '.track',
            '.click',
            '.logout',
        ]);
    }
}
