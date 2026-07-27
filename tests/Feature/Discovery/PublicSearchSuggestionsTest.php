<?php

namespace Tests\Feature\Discovery;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\AssetOffering;
use App\Models\LicenseType;
use App\Models\User;
use App\Models\UserRecentSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSearchSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_endpoint_returns_matching_discoverable_assets(): void
    {
        $asset = $this->discoverableAsset(['title' => 'Golden Beach Walk']);

        $this->getJson('/images/search-suggestions?q=golden')
            ->assertOk()
            ->assertJsonFragment([
                'type' => 'asset',
                'label' => 'Golden Beach Walk',
                'value' => 'Golden Beach Walk',
            ]);
    }

    public function test_signed_in_searches_are_available_as_recent_suggestions(): void
    {
        $user = User::factory()->create();
        $this->discoverableAsset(['title' => 'Quiet Shore']);

        $this->actingAs($user)->get('/images?search=Quiet+Shore')->assertOk();

        $this->assertDatabaseHas('user_recent_searches', [
            'user_id' => $user->id,
            'normalized_term' => 'quiet shore',
        ]);

        $this->actingAs($user)->getJson('/images/search-suggestions')
            ->assertOk()
            ->assertJsonFragment([
                'type' => 'recent',
                'label' => 'Quiet Shore',
            ]);
    }

    public function test_anonymous_empty_query_can_return_popular_searches(): void
    {
        AnalyticsEvent::query()->create([
            'event_uuid' => fake()->uuid(),
            'fingerprint' => hash('sha256', 'popular-search'),
            'event_name' => AnalyticsEventName::SearchPerformed,
            'dimensions' => ['term' => 'summer beach', 'result_count' => 4],
            'occurred_at' => now(),
        ]);

        $this->getJson('/images/search-suggestions')
            ->assertOk()
            ->assertJsonFragment([
                'type' => 'popular',
                'label' => 'summer beach',
            ]);
    }

    public function test_short_non_empty_query_returns_no_suggestions(): void
    {
        $this->getJson('/images/search-suggestions?q=a')
            ->assertOk()
            ->assertExactJson(['suggestions' => []]);
    }

    private function discoverableAsset(array $attributes): Asset
    {
        $asset = Asset::factory()->create($attributes + [
            'status' => 'published',
            'is_active' => true,
            'published_at' => now(),
        ]);

        AssetFile::factory()->create([
            'asset_id' => $asset->id,
            'role' => 'preview',
            'media_type' => 'image',
            'extension' => 'jpg',
            'is_active' => true,
            'is_downloadable' => true,
        ]);

        $license = LicenseType::factory()->create(['is_active' => true]);
        AssetOffering::factory()->create([
            'asset_id' => $asset->id,
            'license_type_id' => $license->id,
            'is_active' => true,
        ]);

        return $asset->fresh();
    }
}
