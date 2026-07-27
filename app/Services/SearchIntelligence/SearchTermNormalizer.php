<?php

namespace App\Services\SearchIntelligence;

use Illuminate\Support\Str;

class SearchTermNormalizer
{
    public function normalize(string $term): string
    {
        return Str::of($term)
            ->lower()
            ->replaceMatches('/[\x{2018}\x{2019}\x{201C}\x{201D}]/u', '')
            ->replaceMatches('/[^\pL\pN]+/u', ' ')
            ->squish()
            ->limit(120, '')
            ->toString();
    }
}
