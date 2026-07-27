<?php

namespace App\Services;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\Asset;
use App\Models\AssetFavorite;
use App\Models\RecentlyViewedAsset;
use App\Models\User;
use App\Models\UserAssetAffinity;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserAssetAffinityService
{
    public function rebuild(User $user): int
    {
        $signals = [];
        $assetSignals = collect();

        AssetFavorite::query()->where('user_id', $user->id)->get(['asset_id', 'created_at'])
            ->each(fn ($row) => $assetSignals->push(['asset_id' => $row->asset_id, 'weight' => 8.0, 'at' => $row->created_at]));

        RecentlyViewedAsset::query()->where('user_id', $user->id)->get(['asset_id', 'view_count', 'last_viewed_at'])
            ->each(fn ($row) => $assetSignals->push([
                'asset_id' => $row->asset_id,
                'weight' => min(5.0, 1.25 + log(max(1, $row->view_count), 2)),
                'at' => $row->last_viewed_at,
            ]));

        $eventWeights = [
            AnalyticsEventName::AssetViewed->value => 1.0,
            AnalyticsEventName::AssetFavorited->value => 8.0,
            AnalyticsEventName::AssetAddedToCart->value => 12.0,
            AnalyticsEventName::AssetDownloaded->value => 16.0,
            AnalyticsEventName::OrderPaid->value => 20.0,
        ];

        AnalyticsEvent::query()
            ->where('user_id', $user->id)
            ->where('subject_type', (new Asset())->getMorphClass())
            ->whereNotNull('subject_id')
            ->whereIn('event_name', array_keys($eventWeights))
            ->where('occurred_at', '>=', now()->subDays((int) config('discovery.recommendations.history_days', 180)))
            ->get(['subject_id', 'event_name', 'occurred_at'])
            ->each(function (AnalyticsEvent $event) use ($assetSignals, $eventWeights): void {
                $name = $event->event_name instanceof AnalyticsEventName ? $event->event_name->value : (string) $event->event_name;
                $assetSignals->push(['asset_id' => $event->subject_id, 'weight' => $eventWeights[$name] ?? 0, 'at' => $event->occurred_at]);
            });

        $assetIds = $assetSignals->pluck('asset_id')->unique()->values();
        $assets = Asset::query()->whereKey($assetIds)->with(['categories:id', 'tags:id'])->get()->keyBy('id');
        $halfLife = max(1, (float) config('discovery.recommendations.affinity_half_life_days', 45));

        foreach ($assetSignals as $signal) {
            $asset = $assets->get((int) $signal['asset_id']);
            if (! $asset) continue;
            $ageDays = $signal['at'] ? max(0, $signal['at']->diffInHours(now()) / 24) : 0;
            $weight = (float) $signal['weight'] * pow(0.5, $ageDays / $halfLife);
            $this->add($signals, 'asset_type', $asset->asset_type->value, $weight);
            if ($asset->collection_id) $this->add($signals, 'collection', (string) $asset->collection_id, $weight * 1.2);
            foreach ($asset->categories as $category) $this->add($signals, 'category', (string) $category->id, $weight * 1.35);
            foreach ($asset->tags as $tag) $this->add($signals, 'tag', (string) $tag->id, $weight);
        }

        DB::transaction(function () use ($user, $signals): void {
            UserAssetAffinity::query()->where('user_id', $user->id)->delete();
            foreach ($signals as $dimension => $values) {
                foreach ($values as $value => $entry) {
                    UserAssetAffinity::query()->create([
                        'user_id' => $user->id,
                        'dimension' => $dimension,
                        'value' => (string) $value,
                        'score' => round($entry['score'], 4),
                        'signal_count' => $entry['count'],
                        'calculated_at' => now(),
                    ]);
                }
            }
        });

        return collect($signals)->sum(fn (array $values) => count($values));
    }

    public function forUser(User $user): Collection
    {
        $latest = UserAssetAffinity::query()->where('user_id', $user->id)->max('calculated_at');
        if (! $latest || Carbon::parse($latest)->lt(now()->subHours((int) config('discovery.recommendations.profile_refresh_hours', 24)))) {
            $this->rebuild($user);
        }
        return UserAssetAffinity::query()->where('user_id', $user->id)->orderByDesc('score')->get();
    }

    private function add(array &$signals, string $dimension, string $value, float $score): void
    {
        $signals[$dimension][$value]['score'] = ($signals[$dimension][$value]['score'] ?? 0) + $score;
        $signals[$dimension][$value]['count'] = ($signals[$dimension][$value]['count'] ?? 0) + 1;
    }
}
