<?php

namespace Database\Seeders;

use App\Models\StudioCreditPackage;
use Illuminate\Database\Seeder;

class StudioCreditPackageSeeder extends Seeder
{
    public function run(): void
    {
        // Creative Studio licensing/billing examples. These are intentionally
        // separate from LicenseType: asset licenses grant content rights while
        // Studio credits pay for successful finished-design exports.
        $packages = [
            [
                'slug' => 'studio-single-export',
                'name' => 'Studio Export Credit',
                'description' => 'One finished Creative Studio export. Re-downloading the same completed export does not use another credit.',
                'credits' => 1,
                'price_cents' => 100,
                'currency' => 'USD',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'slug' => 'studio-10-pack',
                'name' => 'Studio 10-Pack',
                'description' => 'Ten finished Creative Studio exports.',
                'credits' => 10,
                'price_cents' => 800,
                'currency' => 'USD',
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'slug' => 'studio-50-pack',
                'name' => 'Studio 50-Pack',
                'description' => 'Fifty finished Creative Studio exports.',
                'credits' => 50,
                'price_cents' => 3000,
                'currency' => 'USD',
                'is_active' => true,
                'sort_order' => 30,
            ],
        ];

        foreach ($packages as $package) {
            StudioCreditPackage::updateOrCreate(['slug' => $package['slug']], $package);
        }
    }
}
