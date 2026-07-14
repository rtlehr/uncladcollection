<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\SiteSetting;
use App\Services\BrandingAssetService;
use App\Services\SiteSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingController extends Controller
{
    private const BRANDING_UPLOADS = [
        'logo_full' => ['mimes:svg,png,webp', 4096],
        'logo_horizontal' => ['mimes:svg,png,webp', 4096],
        'logo_icon' => ['mimes:svg,png,webp', 4096],
        'logo_light' => ['mimes:svg,png,webp', 4096],
        'logo_dark' => ['mimes:svg,png,webp', 4096],
        'watermark_logo' => ['mimes:png,webp', 4096],
        'social_image' => ['mimes:jpg,jpeg,png,webp', 8192],
        'email_logo' => ['mimes:jpg,jpeg,png,webp', 4096],
        'app_icon' => ['mimes:jpg,jpeg,png,webp', 4096],
    ];

    public function index(): Response
    {
        $groupOrder = ['general','branding','community','commerce','seo','email','social','homepage'];
        $settings = SiteSetting::query()
            ->orderByRaw("FIELD(group_name, '".implode("','", $groupOrder)."')")
            ->orderBy('setting_key')->get()->groupBy('group_name');

        return Inertia::render('Admin/SiteSettings/Index', [
            'settings' => $settings,
            'heroAssetOptions' => Asset::query()->published()->orderBy('title')->get(['id','title','asset_type'])
                ->map(fn (Asset $asset) => ['value'=>(string)$asset->id,'label'=>$asset->title.' · '.$asset->asset_type->value])->values(),
        ]);
    }

    public function update(Request $request, BrandingAssetService $branding): RedirectResponse
    {
        $rules = [
            'settings' => ['required','array'],
            'settings.*.id' => ['required','exists:site_settings,id'],
            'settings.*.setting_value' => ['nullable'],
            'branding_remove' => ['sometimes','array'],
            'branding_remove.*' => ['boolean'],
        ];
        foreach (self::BRANDING_UPLOADS as $key => [$mimeRule, $max]) {
            $rules["branding_uploads.{$key}"] = ['nullable','file',$mimeRule,"max:{$max}"];
        }
        $validated = $request->validate($rules);

        DB::transaction(function () use ($request, $validated, $branding) {
            foreach ($validated['settings'] as $settingData) {
                SiteSetting::whereKey($settingData['id'])->update(['setting_value'=>$settingData['setting_value'] ?? null]);
            }

            foreach (self::BRANDING_UPLOADS as $key => $_rules) {
                $setting = SiteSetting::query()->where('group_name','branding')->where('setting_key',$key)->first();
                if (! $setting) continue;

                if ((bool) data_get($validated, "branding_remove.{$key}", false)) {
                    $branding->delete($setting->setting_value);
                    $setting->update(['setting_value'=>null]);
                }

                if ($file = $request->file("branding_uploads.{$key}")) {
                    try {
                        $url = $branding->store($file, $key);
                        $setting->update(['setting_value'=>$url]);
                        if ($key === 'app_icon') $branding->generateAppIcons($file);
                    } catch (\RuntimeException $exception) {
                        throw ValidationException::withMessages(["branding_uploads.{$key}"=>$exception->getMessage()]);
                    }
                }
            }
        });

        app(SiteSettingService::class)->clearCache();
        return redirect()->route('admin.site-settings.index')->with('success','Site settings updated.');
    }
}
