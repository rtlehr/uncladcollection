<?php

namespace App\Services\PageHelp;

use Illuminate\Support\Collection;

class PageHelpRegistry
{
    public function all(): array
    {
        return collect(config('page-help.registry', []))
            ->map(fn (array $value, string $key) => array_merge(['key' => $key], $value))
            ->sortBy(['area', 'name'])
            ->values()
            ->all();
    }

    public function keys(): array
    {
        return array_keys(config('page-help.registry', []));
    }

    public function get(string $key): ?array
    {
        $value = config("page-help.registry.{$key}");

        return is_array($value) ? array_merge(['key' => $key], $value) : null;
    }

    public function forRoute(?string $routeName): ?array
    {
        if (! $routeName) {
            return null;
        }

        return Collection::make($this->all())
            ->first(fn (array $page) => ($page['route'] ?? null) === $routeName);
    }
}
