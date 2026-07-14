<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $legacyLogo = SiteSetting::query()->where('group_name','branding')->where('setting_key','site_logo')->value('setting_value');
        $legacyFavicon = SiteSetting::query()->where('group_name','branding')->where('setting_key','site_favicon')->value('setting_value');

        $settings = [
            ['logo_full', $legacyLogo, 'image', 'Full stacked logo for spacious layouts, landing pages, and promotional areas.'],
            ['logo_horizontal', null, 'image', 'Horizontal logo for the public desktop header. Falls back to the full logo.'],
            ['logo_icon', null, 'image', 'Graphic-only logo for compact navigation and square placements.'],
            ['logo_light', null, 'image', 'Light logo treatment for dark backgrounds.'],
            ['logo_dark', null, 'image', 'Dark logo treatment for light backgrounds.'],
            ['watermark_logo', null, 'image', 'Transparent PNG or WebP applied to protected image previews.'],
            ['social_image', null, 'image', 'Default 1200 × 630 sharing image for pages without their own social image.'],
            ['email_logo', null, 'image', 'PNG or WebP logo optimized for email. Falls back to the horizontal logo.'],
            ['app_icon', $legacyFavicon, 'image', 'Square master icon used to generate browser and device icons.'],
            ['watermark_enabled', 'true', 'boolean', 'Enable branded watermarks on generated marketplace previews.'],
            ['watermark_opacity', '70', 'integer', 'Watermark opacity from 10 to 100 percent.'],
            ['watermark_position', 'center', 'text', 'Default watermark placement on generated previews.'],
            ['watermark_scale', '35', 'integer', 'Watermark width as a percentage of the image width.'],
            ['watermark_margin', '24', 'integer', 'Watermark edge margin in pixels.'],
        ];

        foreach ($settings as [$key,$value,$type,$description]) {
            SiteSetting::query()->firstOrCreate(
                ['group_name'=>'branding','setting_key'=>$key],
                ['setting_value'=>$value,'setting_type'=>$type,'description'=>$description,'is_public'=>true]
            );
        }
    }

    public function down(): void
    {
        SiteSetting::query()->where('group_name','branding')->whereIn('setting_key', [
            'logo_full','logo_horizontal','logo_icon','logo_light','logo_dark','watermark_logo','social_image','email_logo','app_icon',
            'watermark_enabled','watermark_opacity','watermark_position','watermark_scale','watermark_margin',
        ])->delete();
    }
};
