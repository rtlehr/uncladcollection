<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'group_name' => 'general',
                'setting_key' => 'site_name',
                'setting_value' => 'Unclad Collection',
                'setting_type' => 'text',
                'description' => 'The public name of the website.',
                'is_public' => true,
            ],
            [
                'group_name' => 'general',
                'setting_key' => 'site_tagline',
                'setting_value' => null,
                'setting_type' => 'text',
                'description' => 'Optional tagline displayed with the site name.',
                'is_public' => true,
            ],
            [
                'group_name' => 'general',
                'setting_key' => 'contact_email',
                'setting_value' => 'info@uncladcollection.com',
                'setting_type' => 'email',
                'description' => 'Primary public contact email address.',
                'is_public' => true,
            ],
            [
                'group_name' => 'branding',
                'setting_key' => 'active_theme',
                'setting_value' => 'professional',
                'setting_type' => 'text',
                'description' => 'Current active site theme.',
                'is_public' => true,
            ],
            [
                'group_name' => 'branding',
                'setting_key' => 'primary_color',
                'setting_value' => '#1E2A38',
                'setting_type' => 'color',
                'description' => 'Primary brand color.',
                'is_public' => true,
            ],
            [
                'group_name' => 'branding',
                'setting_key' => 'secondary_color',
                'setting_value' => '#50634D',
                'setting_type' => 'color',
                'description' => 'Secondary brand color.',
                'is_public' => true,
            ],
            [
                'group_name' => 'community',
                'setting_key' => 'allow_comments',
                'setting_value' => 'true',
                'setting_type' => 'boolean',
                'description' => 'Allow users to comment on posts.',
                'is_public' => false,
            ],
            [
                'group_name' => 'community',
                'setting_key' => 'require_comment_approval',
                'setting_value' => 'true',
                'setting_type' => 'boolean',
                'description' => 'Require admin approval before comments appear publicly.',
                'is_public' => false,
            ],
            [
                'group_name' => 'commerce',
                'setting_key' => 'currency',
                'setting_value' => 'USD',
                'setting_type' => 'text',
                'description' => 'Default store currency.',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            SiteSetting::updateOrCreate(
                [
                    'group_name' => $setting['group_name'],
                    'setting_key' => $setting['setting_key'],
                ],
                $setting
            );
        }
    }
}