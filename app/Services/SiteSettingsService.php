<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class SiteSettingService
{
    /**
     * Get one setting using dot format.
     *
     * Example:
     * site_setting('general.site_name')
     */
    public function get(string $key, mixed $default = null): mixed
    {
        [$group, $settingKey] = $this->splitKey($key);

        $settings = $this->all();

        return $settings[$group][$settingKey] ?? $default;
    }

    /**
     * Get all settings grouped by group_name.
     */
    public function all(): array
    {
        return Cache::rememberForever('site_settings', function () {
            return SiteSetting::query()
                ->get()
                ->groupBy('group_name')
                ->map(function ($group) {
                    return $group->mapWithKeys(function ($setting) {
                        return [
                            $setting->setting_key => $this->castValue(
                                $setting->setting_value,
                                $setting->setting_type
                            ),
                        ];
                    });
                })
                ->toArray();
        });
    }

    /**
     * Get only public settings.
     */
    public function public(): array
    {
        return Cache::rememberForever('public_site_settings', function () {
            return SiteSetting::query()
                ->where('is_public', true)
                ->get()
                ->groupBy('group_name')
                ->map(function ($group) {
                    return $group->mapWithKeys(function ($setting) {
                        return [
                            $setting->setting_key => $this->castValue(
                                $setting->setting_value,
                                $setting->setting_type
                            ),
                        ];
                    });
                })
                ->toArray();
        });
    }

    /**
     * Clear settings cache.
     */
    public function clearCache(): void
    {
        Cache::forget('site_settings');
        Cache::forget('public_site_settings');
    }

    private function splitKey(string $key): array
    {
        $parts = explode('.', $key, 2);

        return [
            $parts[0] ?? 'general',
            $parts[1] ?? '',
        ];
    }

    private function castValue(?string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => $value !== null ? (int) $value : null,
            'float' => $value !== null ? (float) $value : null,
            'json' => $value ? json_decode($value, true) : null,
            default => $value,
        };
    }
}