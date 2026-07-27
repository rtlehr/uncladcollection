<?php

namespace App\Services;

use App\Models\HomepageDiscoverySection;
use Illuminate\Support\Collection;

class HomepageDiscoveryService
{
    public function compose(bool $authenticated, array $payloads): array
    {
        $seenAssetIds = [];

        return HomepageDiscoverySection::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (HomepageDiscoverySection $section): bool => $section->visibleTo($authenticated))
            ->map(function (HomepageDiscoverySection $section) use ($payloads, &$seenAssetIds): array {
                $items = collect($payloads[$section->section_key] ?? []);

                if (in_array($section->section_key, ['recommended', 'trending'], true)) {
                    $items = $items->filter(function (array $item) use (&$seenAssetIds): bool {
                        $id = (int) ($item['id'] ?? 0);
                        if ($id < 1 || in_array($id, $seenAssetIds, true)) {
                            return false;
                        }
                        $seenAssetIds[] = $id;
                        return true;
                    });
                }

                return [
                    'key' => $section->section_key,
                    'label' => $section->label,
                    'eyebrow' => $section->eyebrow,
                    'heading' => $section->heading,
                    'description' => $section->description,
                    'item_limit' => $section->item_limit,
                    'items' => $items->take($section->item_limit)->values()->all(),
                ];
            })
            ->filter(fn (array $section): bool => count($section['items']) > 0)
            ->values()
            ->all();
    }
}
