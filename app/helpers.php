<?php

use App\Services\SiteSettingService;

if (! function_exists('site_setting')) {
    function site_setting(string $key, mixed $default = null): mixed
    {
        return app(SiteSettingService::class)->get($key, $default);
    }
}

if (! function_exists('public_site_settings')) {
    function public_site_settings(): array
    {
        return app(SiteSettingService::class)->public();
    }
}