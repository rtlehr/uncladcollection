<?php

use App\Enums\AssetFileProcessingStatus;
use App\Enums\AssetFileRole;
use App\Enums\AssetFileScanStatus;
use App\Enums\AssetMediaType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\AnalyticsEvent;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\Category;
use App\Models\RecentlyViewedAsset;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    expect(DB::connection()->getDriverName())->toBe('mysql');
    expect(DB::connection()->getDatabaseName())->toBe('uncladcollection_testing');
});

it('shows a guest recently viewed asset on the next asset detail page', function (): void {
    $first = epic54Asset('First viewed asset');
    $second = epic54Asset('Second viewed asset');

    $this->get(route('assets.show', $first))->assertOk();

    $this->get(route('assets.show', $second))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('recentlyViewedAssets', 1)
            ->where('recentlyViewedAssets.0.id', $first->id)
            ->where('recentlyViewedAssets.0.reason', 'Recently viewed'));
});

it('persists recently viewed history for signed-in users across sessions', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $first = epic54Asset('Persistent first asset');
    $second = epic54Asset('Persistent second asset');

    $this->actingAs($user)->get(route('assets.show', $first))->assertOk();

    expect(RecentlyViewedAsset::query()
        ->where('user_id', $user->id)
        ->where('asset_id', $first->id)
        ->exists())->toBeTrue();

    $this->flushSession();

    $this->actingAs($user)
        ->get(route('assets.show', $second))
        ->assertInertia(fn ($page) => $page
            ->where('recentlyViewedAssets.0.id', $first->id));
});

it('deduplicates repeated views inside the configured window', function (): void {
    $asset = epic54Asset('Deduplicated asset');

    $this->get(route('assets.show', $asset))->assertOk();
    $this->get(route('assets.show', $asset))->assertOk();

    expect($asset->fresh()->views_count)->toBe(1);
    expect(AnalyticsEvent::query()
        ->where('event_name', 'asset_viewed')
        ->where('subject_id', $asset->id)
        ->count())->toBe(1);
});

it('ranks shared collection and taxonomy above a generic same-type asset', function (): void {
    $source = epic54Asset('Source asset');
    $strong = epic54Asset('Strong related asset');
    $generic = epic54Asset('Generic related asset');

    $category = Category::query()->create([
        'name' => 'Beach Life',
        'slug' => 'beach-life-'.Str::lower(Str::random(5)),
        'category_type' => 'image',
    ]);
    $tag = Tag::query()->create([
        'name' => 'Waterfront',
        'slug' => 'waterfront-'.Str::lower(Str::random(5)),
        'tag_type' => 'image',
    ]);

    $source->categories()->attach($category);
    $source->tags()->attach($tag);
    $strong->categories()->attach($category);
    $strong->tags()->attach($tag);

    $this->get(route('assets.show', $source))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('relatedAssets.0.id', $strong->id)
            ->where('relatedAssets.0.reason', 'Similar subject'));
});

function epic54Asset(string $title): Asset
{
    $asset = Asset::query()->create([
        'uuid' => (string) Str::uuid(),
        'title' => $title,
        'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
        'asset_type' => AssetType::Image,
        'status' => AssetStatus::Published,
        'is_active' => true,
        'published_at' => now()->subMinute(),
    ]);

    $file = AssetFile::query()->create([
        'asset_id' => $asset->id,
        'uuid' => (string) Str::uuid(),
        'role' => AssetFileRole::Preview,
        'media_type' => AssetMediaType::Image,
        'disk' => 'asset-files',
        'directory' => 'assets/testing/'.$asset->uuid.'/preview',
        'stored_filename' => Str::ulid().'.jpg',
        'original_filename' => 'preview.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 1024,
        'checksum_sha256' => hash('sha256', $asset->uuid),
        'sort_order' => 10,
        'width' => 1200,
        'height' => 800,
        'processing_status' => AssetFileProcessingStatus::Ready,
        'virus_scan_status' => AssetFileScanStatus::NotRequired,
        'is_downloadable' => false,
        'is_active' => true,
        'is_legacy' => false,
    ]);

    $asset->update(['primary_preview_file_id' => $file->id]);

    return $asset->fresh();
}
