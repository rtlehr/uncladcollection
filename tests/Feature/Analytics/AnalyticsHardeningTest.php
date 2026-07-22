<?php

namespace Tests\Feature\Analytics;

use App\Analytics\AnalyticsTracker;
use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracker_deduplicates_repeated_events_and_sanitizes_dimensions(): void
    {
        config()->set('analytics.exclude_bots', false);
        config()->set('analytics.deduplicate', true);
        config()->set('analytics.maximum_dimension_string_length', 10);

        $tracker = app(AnalyticsTracker::class);
        $first = $tracker->record(AnalyticsEventName::SearchPerformed, dimensions: ['term' => str_repeat('x', 25)]);
        $second = $tracker->record(AnalyticsEventName::SearchPerformed, dimensions: ['term' => str_repeat('x', 25)]);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('analytics_events', 1);
        $this->assertSame(str_repeat('x', 10), $first->fresh()->dimensions['term']);
        $this->assertNotNull($first->fresh()->fingerprint);
    }

    public function test_prune_command_supports_dry_run_and_deletion(): void
    {
        AnalyticsEvent::query()->create([
            'event_uuid' => (string) str()->uuid(),
            'event_name' => AnalyticsEventName::AssetViewed,
            'occurred_at' => now()->subDays(40),
        ]);

        $this->artisan('analytics:prune --days=30 --dry-run')->assertSuccessful();
        $this->assertDatabaseCount('analytics_events', 1);

        $this->artisan('analytics:prune --days=30')->assertSuccessful();
        $this->assertDatabaseCount('analytics_events', 0);
    }
}
