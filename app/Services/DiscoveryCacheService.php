<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class DiscoveryCacheService
{
    public function version(): int
    {
        return (int) Cache::get(config('discovery.cache_version_key'), 1);
    }

    public function key(string $name, array $parts = []): string
    {
        $suffix = collect($parts)->map(fn ($value, $key) => $key.'='.$value)->implode(':');

        return 'discovery:v'.$this->version().':'.$name.($suffix !== '' ? ':'.$suffix : '');
    }

    public function invalidate(): void
    {
        $key = config('discovery.cache_version_key');
        Cache::forever($key, $this->version() + 1);
    }
}
