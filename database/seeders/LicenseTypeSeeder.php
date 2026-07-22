<?php

namespace Database\Seeders;

use App\Models\LicenseType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LicenseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $licenseTypes = [
            [
                'name' => 'Personal Use',
                'description' => 'Allows personal, non-commercial use of the purchased asset.',
                'price_cents' => 999,
                'image_unit_price_cents' => 999,
                'video_unit_price_cents' => 1999,
                'minimum_price_cents' => 499,
                'currency' => 'USD', 'download_limit' => 5, 'expires_after_days' => null,
                'max_resolution' => 'high_res',
                'usage_terms' => 'This license allows personal, non-commercial use only. The asset may not be resold, redistributed, used in advertising, or used in commercial products.',
                'is_active' => true, 'sort_order' => 1,
            ],
            [
                'name' => 'Commercial Use',
                'description' => 'Allows commercial use for marketing, websites, social media, and business materials.',
                'price_cents' => 2999,
                'image_unit_price_cents' => 2999,
                'video_unit_price_cents' => 5999,
                'minimum_price_cents' => 1499,
                'currency' => 'USD', 'download_limit' => 10, 'expires_after_days' => null,
                'max_resolution' => 'high_res',
                'usage_terms' => 'This license allows commercial use in websites, advertisements, social media, marketing materials, and business publications. The asset may not be resold, sublicensed, or redistributed as a standalone file.',
                'is_active' => true, 'sort_order' => 2,
            ],
            [
                'name' => 'Extended Commercial Use',
                'description' => 'Allows broader commercial use, including higher-volume campaigns and printed products.',
                'price_cents' => 6999,
                'image_unit_price_cents' => 6999,
                'video_unit_price_cents' => 11999,
                'minimum_price_cents' => 2999,
                'currency' => 'USD', 'download_limit' => null, 'expires_after_days' => null,
                'max_resolution' => 'original',
                'usage_terms' => 'This license allows extended commercial use, including large campaigns, print materials, and use in commercial products. The asset may not be resold, sublicensed, or redistributed as a standalone file.',
                'is_active' => true, 'sort_order' => 3,
            ],
        ];

        foreach ($licenseTypes as $licenseType) {
            LicenseType::updateOrCreate(
                ['slug' => Str::slug($licenseType['name'])],
                [...$licenseType, 'slug' => Str::slug($licenseType['name'])],
            );
        }
    }
}
