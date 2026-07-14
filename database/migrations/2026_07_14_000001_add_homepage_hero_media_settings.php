<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            ['hero_asset_id', null, 'integer', 'Published Asset used as homepage hero media.'],
            ['hero_media_mode', 'automatic', 'text', 'Hero mode: automatic, image, or video.'],
            ['hero_video_autoplay_first_visit', 'true', 'boolean', 'Autoplay muted video once per browser session.'],
            ['hero_video_loop', 'true', 'boolean', 'Loop the hero video while it is playing.'],
            ['hero_mobile_autoplay', 'false', 'boolean', 'Allow autoplay on small-screen devices.'],
            ['hero_overlay_opacity', '20', 'integer', 'Media overlay opacity from 0 to 80.'],
            ['hero_media_position', 'center', 'text', 'Media focal position: center, top, bottom, left, or right.'],
            ['hero_height', 'large', 'text', 'Hero height: compact, medium, large, or fullscreen.'],
            ['hero_text_alignment', 'left', 'text', 'Hero text alignment: left, center, or right.'],
            ['hero_show_asset_caption', 'true', 'boolean', 'Show the selected Asset title and creator over the media.'],
        ];

        foreach ($settings as [$key, $value, $type, $description]) {
            SiteSetting::query()->firstOrCreate(
                ['group_name' => 'homepage', 'setting_key' => $key],
                ['setting_value' => $value, 'setting_type' => $type, 'description' => $description, 'is_public' => true],
            );
        }
    }

    public function down(): void
    {
        SiteSetting::query()->where('group_name', 'homepage')->whereIn('setting_key', [
            'hero_asset_id','hero_media_mode','hero_video_autoplay_first_visit','hero_video_loop',
            'hero_mobile_autoplay','hero_overlay_opacity','hero_media_position','hero_height',
            'hero_text_alignment','hero_show_asset_caption',
        ])->delete();
    }
};
