<?php

use App\Enums\AssetFileProcessingStatus;
use App\Enums\AssetFileRole;
use App\Enums\AssetFileScanStatus;
use App\Enums\AssetMediaType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('excludes published assets without presentation media from discovery', function (): void {
    $asset = discoveryAsset();

    expect(Asset::query()->discoverable()->whereKey($asset)->exists())->toBeFalse();
});

it('uses asset-native categories and tags in catalog filtering', function (): void {
    $category = Category::query()->create(['name' => 'Lifestyle', 'slug' => 'lifestyle', 'category_type' => 'image', 'is_active' => true]);
    $tag = Tag::query()->create(['name' => 'Beach', 'slug' => 'beach', 'tag_type' => 'image']);
    $asset = discoveryAsset();
    discoveryPreview($asset);
    $asset->categories()->attach($category);
    $asset->tags()->attach($tag);

    $this->get(route('images.index', ['category_id' => $category->id, 'tag_id' => $tag->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('assets.data', 1)
            ->where('assets.data.0.id', $asset->id)
            ->where('assets.data.0.categories.0.id', $category->id)
            ->where('assets.data.0.tags.0.id', $tag->id));
});

it('allows a non legacy asset to be favorited', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $asset = discoveryAsset(['asset_type' => AssetType::Video]);
    discoveryPreview($asset, AssetMediaType::Video, 'mp4');

    $this->actingAs($user)->post(route('assets.favorite', $asset))->assertRedirect();

    $this->assertDatabaseHas('asset_favorites', ['user_id' => $user->id, 'asset_id' => $asset->id]);
    expect($asset->fresh()->favorites_count)->toBe(1);

    $this->actingAs($user)->delete(route('assets.unfavorite', $asset))->assertRedirect();
    $this->assertDatabaseMissing('asset_favorites', ['user_id' => $user->id, 'asset_id' => $asset->id]);
});

function discoveryAsset(array $overrides = []): Asset
{
    return Asset::query()->create(array_merge([
        'uuid' => (string) Str::uuid(),
        'title' => 'Discovery Asset '.Str::random(6),
        'slug' => 'discovery-asset-'.Str::lower(Str::random(8)),
        'asset_type' => AssetType::Image,
        'status' => AssetStatus::Published,
        'is_active' => true,
        'published_at' => now()->subMinute(),
    ], $overrides));
}

function discoveryPreview(Asset $asset, AssetMediaType $mediaType = AssetMediaType::Image, string $extension = 'jpg'): AssetFile
{
    return AssetFile::query()->create([
        'asset_id' => $asset->id,
        'uuid' => (string) Str::uuid(),
        'role' => AssetFileRole::Preview,
        'media_type' => $mediaType,
        'disk' => 'asset-files',
        'directory' => 'assets/testing/'.$asset->uuid.'/preview',
        'stored_filename' => Str::ulid().'.'.$extension,
        'original_filename' => 'preview.'.$extension,
        'extension' => $extension,
        'mime_type' => $extension === 'mp4' ? 'video/mp4' : 'image/jpeg',
        'size_bytes' => 1024,
        'checksum_sha256' => hash('sha256', $asset->uuid.$extension),
        'sort_order' => 10,
        'processing_status' => AssetFileProcessingStatus::Ready,
        'virus_scan_status' => AssetFileScanStatus::NotRequired,
        'is_downloadable' => true,
        'is_active' => true,
        'is_legacy' => false,
    ]);
}
