<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\SiteSettingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingController extends Controller
{
    public function index(): Response
    {
        $groupOrder = [
            'general',
            'branding',
            'community',
            'commerce',
            'seo',
            'email',
            'social',
            'homepage',
        ];

        $settings = SiteSetting::query()
            ->orderByRaw("FIELD(group_name, '" . implode("','", $groupOrder) . "')")
            ->orderBy('setting_key')
            ->get()
            ->groupBy('group_name');

        return Inertia::render('Admin/SiteSettings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.id' => ['required', 'exists:site_settings,id'],
            'settings.*.setting_value' => ['nullable'],
        ]);

        foreach ($validated['settings'] as $settingData) {
            SiteSetting::where('id', $settingData['id'])->update([
                'setting_value' => $settingData['setting_value'] ?? null,
            ]);
        }

        app(SiteSettingService::class)->clearCache();

        return redirect()
            ->route('admin.site-settings.index')
            ->with('success', 'Site settings updated.');
    }
}