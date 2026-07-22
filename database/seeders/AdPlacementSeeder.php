<?php

namespace Database\Seeders;

use App\Models\AdPlacement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdPlacementSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Homepage Below Hero', 'code' => 'homepage-below-hero', 'location' => 'homepage', 'format' => 'banner', 'width' => 1200, 'height' => 300],
            ['name' => 'Asset Gallery Inline', 'code' => 'asset-gallery-inline', 'location' => 'asset_gallery', 'format' => 'banner', 'width' => 1200, 'height' => 300],
            ['name' => 'Blog Index Inline', 'code' => 'blog-index-inline', 'location' => 'blog_index', 'format' => 'banner', 'width' => 1200, 'height' => 300],
            ['name' => 'Blog Article After Content', 'code' => 'blog-article-after-content', 'location' => 'blog_article', 'format' => 'banner', 'width' => 760, 'height' => 240],
        ] as $placement) {
            AdPlacement::query()->updateOrCreate(['code' => $placement['code']], array_merge($placement, [
                'uuid' => (string) Str::uuid(), 'max_active_campaigns' => 5, 'base_price_cents' => 0,
                'pricing_model' => 'flat', 'is_active' => true,
                'description' => 'Public advertising placement managed by Epic 2 ad delivery.',
            ]));
        }
    }
}
