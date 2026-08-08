<?php

namespace App\Services;

use App\Models\AdCreative;
use App\Models\AdPlacement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PublicAdDeliveryService
{
    private const ROTATION_TTL_DAYS = 7;

    public function select(string $placementCode): ?array
    {
        $placement = AdPlacement::query()
            ->where('code', $placementCode)
            ->where('is_active', true)
            ->first();

        if (! $placement) {
            return null;
        }

        $creatives = AdCreative::query()
            ->with(['campaign.placements', 'placements'])
            ->whereHas('placements', fn ($query) => $query->whereKey($placement->id))
            ->where('status', 'approved')
            ->whereNotNull('media_path')
            ->whereHas('campaign', fn ($query) => $query->current())
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (AdCreative $creative) => $this->isEligible($creative, $placement))
            ->values();

        if ($creatives->isEmpty()) {
            return null;
        }

        $creative = $this->fairChoice($creatives, $placement);

        return [
            'placement' => [
                'id' => $placement->id,
                'code' => $placement->code,
                'name' => $placement->name,
                'format' => $placement->format,
                'width' => $placement->width,
                'height' => $placement->height,
            ],
            'creative' => [
                'id' => $creative->id,
                'uuid' => $creative->uuid,
                'name' => $creative->name,
                'type' => $creative->creative_type,
                'media_url' => Storage::disk('public')->url($creative->media_path),
                'mime_type' => $creative->mime_type,
                'width' => $creative->width,
                'height' => $creative->height,
                'headline' => $creative->headline,
                'body' => $creative->body,
                'cta_label' => $creative->cta_label,
                'destination_url' => $creative->destination_url,
                'alt_text' => $creative->alt_text ?: $creative->headline ?: $creative->name,
            ],
            'campaign' => [
                'id' => $creative->campaign->id,
                'public_code' => $creative->campaign->public_code,
                'name' => $creative->campaign->name,
            ],
        ];
    }

    private function isEligible(AdCreative $creative, AdPlacement $placement): bool
    {
        if (! $creative->campaign || ! $creative->campaign->placements->contains($placement->id) || ! $creative->placements->contains($placement->id)) {
            return false;
        }

        if ($placement->width && $placement->height && $creative->width && $creative->height) {
            if ((int) $creative->width !== (int) $placement->width || (int) $creative->height !== (int) $placement->height) {
                return false;
            }
        }

        if (! Storage::disk('public')->exists($creative->media_path)) {
            return false;
        }

        return $this->safeUrl($creative->destination_url);
    }

    private function safeUrl(?string $url): bool
    {
        if (! $url) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return true;
        }

        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);
    }

    /**
     * Select at the campaign level first, then rotate creatives inside the
     * selected campaign. This prevents a campaign with many creatives from
     * receiving an unfair share of placement traffic.
     *
     * Campaign selection uses smooth weighted round robin plus two safeguards:
     * - consecutive-display protection, so one campaign cannot dominate a run
     * - starvation protection, so every eligible campaign is guaranteed turns
     */
    private function fairChoice(Collection $creatives, AdPlacement $placement): AdCreative
    {
        $campaignGroups = $creatives
            ->groupBy(fn (AdCreative $creative) => (int) $creative->advertising_campaign_id)
            ->map(function (Collection $campaignCreatives) use ($placement) {
                /** @var AdCreative $first */
                $first = $campaignCreatives->first();
                $pivot = $first->campaign->placements->firstWhere('id', $placement->id)?->pivot;

                return [
                    'campaign_id' => (int) $first->campaign->id,
                    'weight' => max(1, (int) ($pivot?->priority ?? 1)),
                    'creatives' => $campaignCreatives->sortBy('id')->values(),
                ];
            })
            ->values();

        if ($campaignGroups->count() === 1) {
            $group = $campaignGroups->first();
            return $this->rotateSingleCampaignCreative($group['creatives'], $placement, $group['campaign_id']);
        }

        $stateKey = $this->stateKey($placement);
        $lockKey = $stateKey.':lock';

        $rotate = fn () => $this->advanceRotation($campaignGroups, $placement, $stateKey);

        try {
            return Cache::lock($lockKey, 5)->block(2, $rotate);
        } catch (Throwable) {
            // Rotation should never make an otherwise eligible ad fail to render.
            // If a cache lock is unavailable, continue with the same algorithm
            // without the lock; the next request will rebalance the state.
            return $rotate();
        }
    }

    private function advanceRotation(Collection $campaignGroups, AdPlacement $placement, string $stateKey): AdCreative
    {
        $state = Cache::get($stateKey, []);

        $campaignIds = $campaignGroups
            ->pluck('campaign_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $weights = [];
        foreach ($campaignGroups as $group) {
            $weights[(int) $group['campaign_id']] = max(1, (int) $group['weight']);
        }

        $currents = $this->normalizeIntMap($state['currents'] ?? [], $campaignIds);
        $sinceSeen = $this->normalizeIntMap($state['since_seen'] ?? [], $campaignIds);
        $lastCreativeIds = $this->normalizeNullableIntMap($state['last_creative_ids'] ?? [], $campaignIds);

        foreach ($campaignIds as $campaignId) {
            $currents[$campaignId] = ($currents[$campaignId] ?? 0) + $weights[$campaignId];
            $sinceSeen[$campaignId] = ($sinceSeen[$campaignId] ?? 0) + 1;
        }

        $lastCampaignId = isset($state['last_campaign_id']) && in_array((int) $state['last_campaign_id'], $campaignIds, true)
            ? (int) $state['last_campaign_id']
            : null;
        $streak = $lastCampaignId ? max(0, (int) ($state['streak'] ?? 0)) : 0;

        $campaignCount = count($campaignIds);
        $maxConsecutive = $this->maxConsecutiveFor($campaignCount);
        $starvationLimit = max(2, $campaignCount * 2);

        $starvedIds = array_values(array_filter(
            $campaignIds,
            fn (int $campaignId) => ($sinceSeen[$campaignId] ?? 0) >= $starvationLimit,
        ));

        if ($starvedIds !== []) {
            $selectedCampaignId = $this->highestPriorityCampaign($starvedIds, $currents, $sinceSeen);
        } else {
            $selectedCampaignId = $this->highestCurrentCampaign($campaignIds, $currents);

            if (
                $lastCampaignId !== null
                && $selectedCampaignId === $lastCampaignId
                && $streak >= $maxConsecutive
            ) {
                $alternatives = array_values(array_filter(
                    $campaignIds,
                    fn (int $campaignId) => $campaignId !== $lastCampaignId,
                ));

                if ($alternatives !== []) {
                    $selectedCampaignId = $this->highestCurrentCampaign($alternatives, $currents);
                }
            }
        }

        $totalWeight = array_sum($weights);
        $currents[$selectedCampaignId] = ($currents[$selectedCampaignId] ?? 0) - $totalWeight;
        $sinceSeen[$selectedCampaignId] = 0;

        if ($lastCampaignId === $selectedCampaignId) {
            $streak++;
        } else {
            $lastCampaignId = $selectedCampaignId;
            $streak = 1;
        }

        $selectedGroup = $campaignGroups->firstWhere('campaign_id', $selectedCampaignId);
        /** @var Collection<int, AdCreative> $selectedCreatives */
        $selectedCreatives = $selectedGroup['creatives'];

        $creative = $this->nextCreative(
            $selectedCreatives,
            $lastCreativeIds[$selectedCampaignId] ?? null,
        );
        $lastCreativeIds[$selectedCampaignId] = (int) $creative->id;

        Cache::put($stateKey, [
            'currents' => $currents,
            'since_seen' => $sinceSeen,
            'last_campaign_id' => $lastCampaignId,
            'streak' => $streak,
            'last_creative_ids' => $lastCreativeIds,
        ], now()->addDays(self::ROTATION_TTL_DAYS));

        return $creative;
    }

    private function rotateSingleCampaignCreative(Collection $creatives, AdPlacement $placement, int $campaignId): AdCreative
    {
        if ($creatives->count() === 1) {
            return $creatives->first();
        }

        $stateKey = $this->stateKey($placement).':campaign:'.$campaignId;
        $lockKey = $stateKey.':lock';

        $rotate = function () use ($creatives, $stateKey): AdCreative {
            $lastCreativeId = Cache::get($stateKey);
            $creative = $this->nextCreative($creatives, $lastCreativeId ? (int) $lastCreativeId : null);
            Cache::put($stateKey, (int) $creative->id, now()->addDays(self::ROTATION_TTL_DAYS));
            return $creative;
        };

        try {
            return Cache::lock($lockKey, 5)->block(2, $rotate);
        } catch (Throwable) {
            return $rotate();
        }
    }

    private function nextCreative(Collection $creatives, ?int $lastCreativeId): AdCreative
    {
        $ordered = $creatives->sortBy('id')->values();

        if ($lastCreativeId === null) {
            return $ordered->first();
        }

        $lastIndex = $ordered->search(fn (AdCreative $creative) => (int) $creative->id === $lastCreativeId);

        if ($lastIndex === false) {
            return $ordered->first();
        }

        return $ordered->get(($lastIndex + 1) % $ordered->count());
    }

    private function highestCurrentCampaign(array $campaignIds, array $currents): int
    {
        usort($campaignIds, function (int $a, int $b) use ($currents): int {
            $scoreComparison = ($currents[$b] ?? 0) <=> ($currents[$a] ?? 0);
            return $scoreComparison !== 0 ? $scoreComparison : ($a <=> $b);
        });

        return $campaignIds[0];
    }

    private function highestPriorityCampaign(array $campaignIds, array $currents, array $sinceSeen): int
    {
        usort($campaignIds, function (int $a, int $b) use ($currents, $sinceSeen): int {
            $ageComparison = ($sinceSeen[$b] ?? 0) <=> ($sinceSeen[$a] ?? 0);
            if ($ageComparison !== 0) {
                return $ageComparison;
            }

            $scoreComparison = ($currents[$b] ?? 0) <=> ($currents[$a] ?? 0);
            return $scoreComparison !== 0 ? $scoreComparison : ($a <=> $b);
        });

        return $campaignIds[0];
    }

    private function maxConsecutiveFor(int $campaignCount): int
    {
        if ($campaignCount <= 1) {
            return PHP_INT_MAX;
        }

        if ($campaignCount <= 3) {
            return 2;
        }

        return 1;
    }

    private function normalizeIntMap(array $map, array $validIds): array
    {
        $normalized = [];

        foreach ($validIds as $id) {
            $normalized[$id] = (int) ($map[$id] ?? $map[(string) $id] ?? 0);
        }

        return $normalized;
    }

    private function normalizeNullableIntMap(array $map, array $validIds): array
    {
        $normalized = [];

        foreach ($validIds as $id) {
            $value = $map[$id] ?? $map[(string) $id] ?? null;
            $normalized[$id] = $value === null ? null : (int) $value;
        }

        return $normalized;
    }

    private function stateKey(AdPlacement $placement): string
    {
        return 'advertising:rotation:placement:'.$placement->id.':v2';
    }
}
