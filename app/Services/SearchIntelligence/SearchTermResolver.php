<?php

namespace App\Services\SearchIntelligence;

use App\Models\SearchTermMapping;
use Illuminate\Support\Facades\Cache;

class SearchTermResolver
{
    public function __construct(private readonly SearchTermNormalizer $normalizer) {}

    public function resolve(string $term): array
    {
        $normalized = $this->normalizer->normalize($term);
        if ($normalized === '') return ['original' => '', 'canonical' => '', 'terms' => []];

        $mapping = Cache::remember('search-intelligence:mapping:'.sha1($normalized), now()->addMinutes(30), fn () => SearchTermMapping::query()
            ->where('status', SearchTermMapping::STATUS_APPROVED)
            ->whereHas('searchTerm', fn ($query) => $query->where('normalized_term', $normalized))
            ->first());

        $canonical = $this->normalizer->normalize((string) ($mapping?->approved_canonical_term ?: $normalized));
        $synonyms = collect($mapping?->approved_synonyms ?: [])->map(fn ($value) => $this->normalizer->normalize((string) $value))->filter();

        return ['original' => $normalized, 'canonical' => $canonical, 'terms' => collect([$normalized, $canonical])->concat($synonyms)->filter()->unique()->values()->all()];
    }

    public function flush(string $term): void { Cache::forget('search-intelligence:mapping:'.sha1($this->normalizer->normalize($term))); }
}
