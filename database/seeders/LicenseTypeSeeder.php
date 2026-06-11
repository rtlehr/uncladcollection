<?php

namespace Database\Seeders;

use App\Models\LicenseType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LicenseTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $licenseTypes = [
            [
                'name' => 'Personal Use',
                'description' => 'Allows personal, non-commercial use of the image.',
                'price_cents' => 999,
                'currency' => 'USD',
                'download_limit' => 5,
                'expires_after_days' => null,
                'max_resolution' => 'high_res',
                'usage_terms' => 'This license allows personal, non-commercial use only. The image may not be resold, redistributed, used in advertising, or used in commercial products.',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Commercial Use',
                'description' => 'Allows commercial use for marketing, websites, social media, and business materials.',
                'price_cents' => 2999,
                'currency' => 'USD',
                'download_limit' => 10,
                'expires_after_days' => null,
                'max_resolution' => 'high_res',
                'usage_terms' => 'This license allows commercial use in websites, advertisements, social media, marketing materials, and business publications. The image may not be resold, sublicensed, or redistributed as a standalone file.',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Extended Commercial Use',
                'description' => 'Allows broader commercial use, including higher-volume campaigns and printed products.',
                'price_cents' => 9999,
                'currency' => 'USD',
                'download_limit' => null,
                'expires_after_days' => null,
                'max_resolution' => 'original',
                'usage_terms' => 'This license allows extended commercial use, including large campaigns, print materials, and use in commercial products. The image may not be resold, sublicensed, or redistributed as a standalone file.',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($licenseTypes as $licenseType) {
            LicenseType::updateOrCreate(
                ['slug' => Str::slug($licenseType['name'])],
                [
                    ...$licenseType,
                    'slug' => Str::slug($licenseType['name']),
                ]
            );
        }
    }
}