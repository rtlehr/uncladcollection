<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetOffering;
use App\Models\LicenseType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AssetOffering> */
class AssetOfferingFactory extends Factory
{
    protected $model = AssetOffering::class;

    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'license_type_id' => LicenseType::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'image_units' => 1,
            'video_units' => 0,
            'price_cents' => 1000,
            'price_adjustment_cents' => 0,
            'price_override_cents' => null,
            'currency' => 'USD',
            'download_limit' => 5,
            'expires_after_days' => null,
            'include_all_active_files' => false,
            'is_active' => true,
            'sort_order' => 10,
            'metadata' => [],
        ];
    }

    public function video(): static
    {
        return $this->state(fn () => [
            'image_units' => 0,
            'video_units' => 1,
            'price_cents' => 3000,
        ]);
    }

    public function legacyFixedPrice(): static
    {
        return $this->state(fn () => [
            'image_units' => 0,
            'video_units' => 0,
            'price_cents' => 2000,
        ]);
    }
}
