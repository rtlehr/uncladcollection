<?php

use App\Models\Asset;
use App\Models\AssetFavorite;
use App\Models\AssetFile;
use App\Models\Category;
use App\Models\User;
use App\Services\PersonalizedRecommendationService;
use App\Services\UserAssetAffinityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function recommendationAsset(array $attributes = []): Asset
{
    $asset = Asset::factory()->create(array_merge(['status' => 'published', 'is_active' => true, 'published_at' => now()->subDay()], $attributes));
    AssetFile::factory()->preview()->for($asset)->create();
    return $asset;
}

it('recommends discoverable assets that share a users strongest category affinity', function (): void {
    $user = User::factory()->create();
    $preferred = Category::query()->create(['name' => 'Preferred', 'slug' => 'preferred', 'category_type' => 'image', 'is_active' => true]);
    $other = Category::query()->create(['name' => 'Other', 'slug' => 'other', 'category_type' => 'image', 'is_active' => true]);
    $signal = recommendationAsset();
    $signal->categories()->attach($preferred);
    AssetFavorite::query()->create(['user_id' => $user->id, 'asset_id' => $signal->id]);
    $matching = recommendationAsset();
    $matching->categories()->attach($preferred);
    $unrelated = recommendationAsset();
    $unrelated->categories()->attach($other);
    app(UserAssetAffinityService::class)->rebuild($user);
    $recommendations = app(PersonalizedRecommendationService::class)->forUser($user, 4);
    expect($recommendations->pluck('id'))->toContain($matching->id)->not->toContain($signal->id);
});

it('falls back safely for a user with no behavioral history', function (): void {
    $user = User::factory()->create();
    expect(app(PersonalizedRecommendationService::class)->forUser($user, 6))->toBeInstanceOf(\Illuminate\Support\Collection::class);
});
