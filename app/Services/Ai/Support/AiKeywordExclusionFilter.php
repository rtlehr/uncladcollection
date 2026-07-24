<?php

namespace App\Services\Ai\Support;

use App\Models\AiKeywordExclusion;
use Illuminate\Support\Collection;

final class AiKeywordExclusionFilter
{
    /** @param array<int, mixed> $keywords
     *  @return array<int, string>
     */
    public function filter(array $keywords): array
    {
        $excluded = $this->activeNormalizedKeywords();

        return collect($keywords)
            ->filter(fn (mixed $keyword): bool => is_string($keyword) && trim($keyword) !== '')
            ->map(fn (string $keyword): string => trim($keyword))
            ->reject(fn (string $keyword): bool => $excluded->contains(AiKeywordExclusion::normalize($keyword)))
            ->unique(fn (string $keyword): string => AiKeywordExclusion::normalize($keyword))
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $metadata
     *  @return array<string, mixed>
     */
    public function filterMetadata(array $metadata): array
    {
        if (is_array($metadata['keywords'] ?? null)) {
            $metadata['keywords'] = $this->filter($metadata['keywords']);
        }

        return $metadata;
    }

    /** @return Collection<int, string> */
    private function activeNormalizedKeywords(): Collection
    {
        return AiKeywordExclusion::query()
            ->where('is_active', true)
            ->pluck('normalized_keyword');
    }
}
