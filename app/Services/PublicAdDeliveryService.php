<?php

namespace App\Services;

use App\Models\AdCreative;
use App\Models\AdPlacement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class PublicAdDeliveryService
{
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

        $creative = $this->weightedChoice($creatives, $placement);

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

    private function weightedChoice(Collection $creatives, AdPlacement $placement): AdCreative
    {
        $weighted = $creatives->map(function (AdCreative $creative) use ($placement) {
            $pivot = $creative->campaign->placements->firstWhere('id', $placement->id)?->pivot;
            return ['creative' => $creative, 'weight' => max(1, (int) ($pivot?->priority ?? 1))];
        });

        $total = $weighted->sum('weight');
        $pick = random_int(1, max(1, $total));

        foreach ($weighted as $entry) {
            $pick -= $entry['weight'];
            if ($pick <= 0) {
                return $entry['creative'];
            }
        }

        return $creatives->first();
    }
}
