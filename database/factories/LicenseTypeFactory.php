<?php

namespace Database\Factories;

use App\Models\LicenseType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<LicenseType> */
class LicenseTypeFactory extends Factory
{
    protected $model = LicenseType::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' License';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'description' => fake()->sentence(),
            'price_cents' => 1000,
            'image_unit_price_cents' => 1000,
            'video_unit_price_cents' => 3000,
            'minimum_price_cents' => 500,
            'currency' => 'USD',
            'download_limit' => 5,
            'expires_after_days' => null,
            'max_resolution' => 'high_res',
            'usage_terms' => fake()->paragraph(),
            'is_active' => true,
            'sort_order' => 10,
        ];
    }
}
