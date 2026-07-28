<?php

namespace Database\Factories;

use App\Enums\AssetFulfillmentType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\AssetFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'uuid' => (string) Str::uuid(),
            'legacy_image_id' => null,
            'collection_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(8)),
            'description' => fake()->paragraph(),
            'asset_type' => AssetType::Image,
            'status' => AssetStatus::Draft,
            'photographer' => fake()->name(),
            'sort_order' => 0,
            'is_active' => true,
            'is_featured' => false,
            'is_ai_generated' => false,
            'allows_quantity' => false,
            'fulfillment_type' => AssetFulfillmentType::Digital,
            'collects_shipping_address' => false,
            'shipping_address_required' => false,
            'downloads_count' => 0,
            'favorites_count' => 0,
            'purchases_count' => 0,
            'views_count' => 0,
            'published_at' => null,
            'metadata' => [],
            'presentation_images' => null,
            'primary_preview_file_id' => null,
            'poster_file_id' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => AssetStatus::Published,
            'is_active' => true,
            'published_at' => now(),
        ]);
    }

    public function discoverable(): static
    {
        return $this->published()->afterCreating(function (Asset $asset): void {
            AssetFile::factory()
                ->preview()
                ->for($asset)
                ->create();
        });
    }

    public function video(): static
    {
        return $this->state(fn () => [
            'asset_type' => AssetType::Video,
        ]);
    }

    public function vector(): static
    {
        return $this->state(fn () => [
            'asset_type' => AssetType::Vector,
        ]);
    }
}
