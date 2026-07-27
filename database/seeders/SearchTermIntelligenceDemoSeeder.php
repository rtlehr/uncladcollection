<?php

namespace Database\Seeders;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\SearchTerm;
use App\Models\SearchTermMapping;
use App\Services\SearchIntelligence\SearchTermAggregationService;
use Illuminate\Database\Seeder;
use Carbon\CarbonInterface;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class SearchTermIntelligenceDemoSeeder extends Seeder
{
    public const SOURCE = 'search_intelligence_demo';

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $groups = [
        [
            'normalized' => 'camp ground',
            'variants' => ['Camp Ground' => 8, 'camp-ground' => 5, 'camp ground' => 7],
            'results' => [0, 3, 6, 8],
            'mapping' => [
                'status' => SearchTermMapping::STATUS_APPROVED,
                'suggested_canonical_term' => 'campground',
                'approved_canonical_term' => 'campground',
                'suggested_synonyms' => ['camp ground', 'naturist campground', 'nudist campground'],
                'approved_synonyms' => ['camp ground', 'naturist campground', 'nudist campground'],
                'intent_category' => 'location',
                'confidence' => 0.98,
                'ai_explanation' => 'Capitalization and punctuation variants describe the same campground intent.',
                'is_content_opportunity' => false,
            ],
            'engagement' => ['views' => 11, 'favorites' => 4, 'carts' => 3, 'orders' => 2, 'revenue_cents' => 4800],
        ],
        [
            'normalized' => 'campgroud',
            'variants' => ['campgroud' => 4, 'Campgroud' => 2],
            'results' => [0, 0, 2],
            'mapping' => [
                'status' => SearchTermMapping::STATUS_PENDING,
                'suggested_canonical_term' => 'campground',
                'approved_canonical_term' => null,
                'suggested_synonyms' => ['camp ground', 'naturist campground'],
                'approved_synonyms' => null,
                'intent_category' => 'location',
                'confidence' => 0.97,
                'ai_explanation' => '“Campgroud” is very likely a misspelling of “campground,” but the correction remains pending for review.',
                'is_content_opportunity' => false,
            ],
            'engagement' => ['views' => 1, 'favorites' => 0, 'carts' => 0, 'orders' => 0, 'revenue_cents' => 0],
        ],
        [
            'normalized' => 'nudist beach',
            'variants' => ['nudist beach' => 10, 'Nudist Beach' => 5, 'nudist-beach' => 4],
            'results' => [12, 9, 11],
            'mapping' => [
                'status' => SearchTermMapping::STATUS_APPROVED,
                'suggested_canonical_term' => 'nudist beach',
                'approved_canonical_term' => 'nudist beach',
                'suggested_synonyms' => ['nude beach', 'naturist beach', 'clothing optional beach'],
                'approved_synonyms' => ['nude beach', 'naturist beach', 'clothing optional beach'],
                'intent_category' => 'location',
                'confidence' => 0.96,
                'ai_explanation' => 'These are common equivalent phrases for the same beach setting.',
                'is_content_opportunity' => false,
            ],
            'engagement' => ['views' => 15, 'favorites' => 5, 'carts' => 2, 'orders' => 1, 'revenue_cents' => 1900],
        ],
        [
            'normalized' => 'older couple',
            'variants' => ['older couple' => 7, 'Older Couple' => 4, 'older-couple' => 3],
            'results' => [2, 2, 4, 1],
            'mapping' => [
                'status' => SearchTermMapping::STATUS_PENDING,
                'suggested_canonical_term' => 'mature couples',
                'approved_canonical_term' => null,
                'suggested_synonyms' => ['older couples', 'senior couples', 'mature couple'],
                'approved_synonyms' => null,
                'intent_category' => 'people',
                'confidence' => 0.84,
                'ai_explanation' => 'The phrases are probably related, but age-related wording should be reviewed before merging.',
                'is_content_opportunity' => true,
            ],
            'engagement' => ['views' => 9, 'favorites' => 3, 'carts' => 1, 'orders' => 0, 'revenue_cents' => 0],
        ],
        [
            'normalized' => 'body painting',
            'variants' => ['body painting' => 7, 'Body Painting' => 3, 'body-painting' => 4],
            'results' => [7, 5, 3],
            'mapping' => null,
            'engagement' => ['views' => 7, 'favorites' => 2, 'carts' => 1, 'orders' => 0, 'revenue_cents' => 0],
        ],
        [
            'normalized' => 'volleyball',
            'variants' => ['volleyball' => 9, 'Volleyball' => 4],
            'results' => [0, 0, 0],
            'mapping' => [
                'status' => SearchTermMapping::STATUS_PENDING,
                'suggested_canonical_term' => 'volleyball',
                'approved_canonical_term' => null,
                'suggested_synonyms' => ['beach volleyball', 'naturist volleyball'],
                'approved_synonyms' => null,
                'intent_category' => 'activity',
                'confidence' => 0.94,
                'ai_explanation' => 'Repeated zero-result searches indicate a likely catalog opportunity.',
                'is_content_opportunity' => true,
            ],
            'engagement' => ['views' => 0, 'favorites' => 0, 'carts' => 0, 'orders' => 0, 'revenue_cents' => 0],
        ],
        [
            'normalized' => 'vollyball',
            'variants' => ['vollyball' => 5, 'Vollyball' => 2],
            'results' => [0, 0],
            'mapping' => [
                'status' => SearchTermMapping::STATUS_APPROVED,
                'suggested_canonical_term' => 'volleyball',
                'approved_canonical_term' => 'volleyball',
                'suggested_synonyms' => ['beach volleyball'],
                'approved_synonyms' => ['beach volleyball'],
                'intent_category' => 'activity',
                'confidence' => 0.99,
                'ai_explanation' => '“Vollyball” is a common misspelling of “volleyball.”',
                'is_content_opportunity' => true,
            ],
            'engagement' => ['views' => 0, 'favorites' => 0, 'carts' => 0, 'orders' => 0, 'revenue_cents' => 0],
        ],
        [
            'normalized' => 'waterfall',
            'variants' => ['waterfall' => 4, 'Waterfall' => 3],
            'results' => [8, 1],
            'mapping' => [
                'status' => SearchTermMapping::STATUS_REJECTED,
                'suggested_canonical_term' => 'water feature',
                'approved_canonical_term' => null,
                'suggested_synonyms' => ['water falls'],
                'approved_synonyms' => null,
                'intent_category' => 'setting',
                'confidence' => 0.67,
                'ai_explanation' => 'The broader “water feature” suggestion was rejected because it would weaken the user’s specific waterfall intent.',
                'is_content_opportunity' => false,
            ],
            'engagement' => ['views' => 4, 'favorites' => 1, 'carts' => 0, 'orders' => 0, 'revenue_cents' => 0],
        ],
        [
            'normalized' => 'yoga',
            'variants' => ['yoga' => 6, 'Yoga' => 4],
            'results' => [6, 4, 4],
            'mapping' => [
                'status' => SearchTermMapping::STATUS_APPROVED,
                'suggested_canonical_term' => 'naturist yoga',
                'approved_canonical_term' => 'naturist yoga',
                'suggested_synonyms' => ['nude yoga', 'yoga'],
                'approved_synonyms' => ['nude yoga', 'yoga'],
                'intent_category' => 'activity',
                'confidence' => 0.92,
                'ai_explanation' => 'The terms describe the same activity, with “naturist yoga” providing marketplace context.',
                'is_content_opportunity' => false,
            ],
            'engagement' => ['views' => 8, 'favorites' => 3, 'carts' => 2, 'orders' => 1, 'revenue_cents' => 2400],
        ],
        [
            'normalized' => 'family naturism',
            'variants' => ['family naturism' => 4, 'Family Naturism' => 3, 'family-naturism' => 2],
            'results' => [1, 1],
            'mapping' => [
                'status' => SearchTermMapping::STATUS_PENDING,
                'suggested_canonical_term' => 'family naturism',
                'approved_canonical_term' => null,
                'suggested_synonyms' => ['family nudism'],
                'approved_synonyms' => null,
                'intent_category' => 'lifestyle',
                'confidence' => 0.73,
                'ai_explanation' => 'The terms are often used similarly, but this sensitive topic should receive deliberate administrative review.',
                'is_content_opportunity' => true,
            ],
            'engagement' => ['views' => 2, 'favorites' => 0, 'carts' => 0, 'orders' => 0, 'revenue_cents' => 0],
        ],
        [
            'normalized' => 'sunset',
            'variants' => ['sunset' => 8, 'Sunset' => 4, 'SUNSET' => 2],
            'results' => [15, 5, 15],
            'mapping' => [
                'status' => SearchTermMapping::STATUS_APPROVED,
                'suggested_canonical_term' => 'sunset',
                'approved_canonical_term' => 'sunset',
                'suggested_synonyms' => ['sun set', 'golden hour'],
                'approved_synonyms' => ['sun set', 'golden hour'],
                'intent_category' => 'setting',
                'confidence' => 0.99,
                'ai_explanation' => 'These are capitalization variants of “sunset”; “golden hour” is a related approved synonym.',
                'is_content_opportunity' => false,
            ],
            'engagement' => ['views' => 12, 'favorites' => 5, 'carts' => 3, 'orders' => 2, 'revenue_cents' => 6200],
        ],
        [
            'normalized' => 'black and white',
            'variants' => ['black and white' => 6, 'black-and-white' => 5, 'Black and White' => 3],
            'results' => [9, 9, 7],
            'mapping' => [
                'status' => SearchTermMapping::STATUS_APPROVED,
                'suggested_canonical_term' => 'black and white',
                'approved_canonical_term' => 'black and white',
                'suggested_synonyms' => ['black-and-white', 'monochrome'],
                'approved_synonyms' => ['black-and-white', 'monochrome'],
                'intent_category' => 'style',
                'confidence' => 0.97,
                'ai_explanation' => 'The phrases express the same photographic style and are suitable for approved synonym expansion.',
                'is_content_opportunity' => false,
            ],
            'engagement' => ['views' => 10, 'favorites' => 4, 'carts' => 2, 'orders' => 1, 'revenue_cents' => 3200],
        ],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('SearchTermIntelligenceDemoSeeder may only run in local or testing environments.');
        }

        $baseTime = now()->subDays(20)->startOfDay()->addHours(10);

        foreach ($this->groups as $groupIndex => $group) {
            $sessions = $this->seedSearchEvents($group, $groupIndex, $baseTime->copy()->addDays($groupIndex));
            $this->seedEngagementEvents($group, $groupIndex, $sessions, $baseTime->copy()->addDays($groupIndex));
        }

        app(SearchTermAggregationService::class)->rebuild(365);

        foreach ($this->groups as $group) {
            $term = SearchTerm::query()->where('normalized_term', $group['normalized'])->first();
            if (! $term) {
                continue;
            }

            $mapping = $group['mapping'];
            $term->update([
                'is_content_opportunity' => (bool) ($mapping['is_content_opportunity'] ?? false),
            ]);

            if ($mapping === null) {
                $term->mapping()->delete();
                continue;
            }

            SearchTermMapping::query()->updateOrCreate(
                ['search_term_id' => $term->id],
                [
                    'suggested_canonical_term' => $mapping['suggested_canonical_term'],
                    'approved_canonical_term' => $mapping['approved_canonical_term'],
                    'suggested_synonyms' => $mapping['suggested_synonyms'],
                    'approved_synonyms' => $mapping['approved_synonyms'],
                    'intent_category' => $mapping['intent_category'],
                    'confidence' => $mapping['confidence'],
                    'ai_explanation' => $mapping['ai_explanation'],
                    'status' => $mapping['status'],
                    'source' => 'demo',
                    'provider' => 'ollama',
                    'model' => (string) config('search-intelligence.ollama.model', 'qwen3-vl:8b'),
                    'reviewed_by_user_id' => null,
                    'reviewed_at' => $mapping['status'] === SearchTermMapping::STATUS_APPROVED ? now()->subDays(2) : null,
                    'analyzed_at' => now()->subDays(3),
                ],
            );
        }

        $this->command?->info('Search Term Intelligence demo data seeded successfully.');
        $this->command?->line('Open /admin/discovery/search-intelligence to review the examples.');
    }

    /**
     * @param array<string, mixed> $group
     * @return array<int, string>
     */
    private function seedSearchEvents(array $group, int $groupIndex, CarbonInterface $start): array
    {
        $sessions = [];
        $eventIndex = 0;
        $resultPatterns = $group['results'];

        foreach ($group['variants'] as $rawTerm => $count) {
            for ($i = 0; $i < $count; $i++) {
                $sessionId = sprintf('demo-search-%02d-%03d', $groupIndex, $eventIndex);
                $sessions[] = $sessionId;
                $occurredAt = $start->copy()->addMinutes($eventIndex * 11);
                $resultCount = (int) $resultPatterns[$eventIndex % count($resultPatterns)];
                $eventKey = sprintf('search-%02d-%03d', $groupIndex, $eventIndex);

                $this->upsertEvent($eventKey, [
                    'event_name' => AnalyticsEventName::SearchPerformed,
                    'session_id' => $sessionId,
                    'source' => self::SOURCE,
                    'channel' => 'web',
                    'dimensions' => [
                        'term' => $rawTerm,
                        'result_count' => $resultCount,
                        'demo' => true,
                    ],
                    'occurred_at' => $occurredAt,
                ]);

                $eventIndex++;
            }
        }

        return $sessions;
    }

    /**
     * @param array<string, mixed> $group
     * @param array<int, string> $sessions
     */
    private function seedEngagementEvents(array $group, int $groupIndex, array $sessions, CarbonInterface $start): void
    {
        $engagement = $group['engagement'];
        $cursor = 0;

        foreach ([
            'views' => AnalyticsEventName::AssetViewed,
            'favorites' => AnalyticsEventName::AssetFavorited,
            'carts' => AnalyticsEventName::AssetAddedToCart,
        ] as $metric => $eventName) {
            for ($i = 0; $i < (int) $engagement[$metric]; $i++) {
                $sessionId = $sessions[$cursor % max(1, count($sessions))] ?? sprintf('demo-search-%02d-fallback', $groupIndex);
                $this->upsertEvent(sprintf('%s-%02d-%03d', $metric, $groupIndex, $i), [
                    'event_name' => $eventName,
                    'session_id' => $sessionId,
                    'source' => 'search',
                    'channel' => 'web',
                    'dimensions' => ['demo' => true, 'search_term' => $group['normalized']],
                    'occurred_at' => $start->copy()->addHours(4)->addMinutes($cursor * 7),
                ]);
                $cursor++;
            }
        }

        $orders = (int) $engagement['orders'];
        $revenue = (int) $engagement['revenue_cents'];
        for ($i = 0; $i < $orders; $i++) {
            $sessionId = $sessions[$cursor % max(1, count($sessions))] ?? sprintf('demo-search-%02d-order', $groupIndex);
            $value = $orders > 0 ? intdiv($revenue, $orders) + ($i === 0 ? $revenue % $orders : 0) : 0;
            $this->upsertEvent(sprintf('orders-%02d-%03d', $groupIndex, $i), [
                'event_name' => AnalyticsEventName::OrderPaid,
                'session_id' => $sessionId,
                'source' => 'search',
                'channel' => 'web',
                'currency' => 'USD',
                'value_cents' => $value,
                'dimensions' => ['demo' => true, 'search_term' => $group['normalized']],
                'occurred_at' => $start->copy()->addHours(6)->addMinutes($i * 13),
            ]);
            $cursor++;
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function upsertEvent(string $key, array $attributes): void
    {
        $uuid = Uuid::uuid5(Uuid::NAMESPACE_URL, 'uncladcollection:search-intelligence-demo:'.$key)->toString();

        AnalyticsEvent::query()->updateOrCreate(
            ['event_uuid' => $uuid],
            array_merge([
                'fingerprint' => hash('sha256', 'search-intelligence-demo:'.$key),
                'subject_type' => null,
                'subject_id' => null,
                'user_id' => null,
                'currency' => null,
                'value_cents' => null,
            ], $attributes),
        );
    }
}
