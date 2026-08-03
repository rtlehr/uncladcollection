<?php

namespace App\Services\Ai\ContentStudio;

use App\Models\AiPromptExample;
use Illuminate\Support\Collection;

class PromptExampleSelector
{
    /**
     * @return Collection<int, AiPromptExample>
     */
    public function select(
        string $description,
        string $context,
        string $use,
        string $bodyDetailLevel = 'contextual',
        int $limit = 6,
    ): Collection {
        $keywords = collect(preg_split('/\W+/u', mb_strtolower($description)) ?: [])
            ->filter(fn ($word) => mb_strlen($word) >= 4)
            ->unique()
            ->values();

        return AiPromptExample::query()
            ->where('is_enabled', true)
            ->get()
            ->map(function (AiPromptExample $example) use ($keywords, $context, $use, $bodyDetailLevel) {
                $score = $this->scoreExample($example, $keywords, $context, $use, $bodyDetailLevel);
                $example->relevance_score = $score;

                return $example;
            })
            ->filter(fn (AiPromptExample $example) => $example->relevance_score > 0)
            ->sortByDesc('relevance_score')
            ->take($limit)
            ->values();
    }

    /**
     * @param Collection<int, string> $keywords
     */
    private function scoreExample(
        AiPromptExample $example,
        Collection $keywords,
        string $context,
        string $use,
        string $bodyDetailLevel,
    ): int {
        $text = mb_strtolower($example->title.' '.$example->content.' '.implode(' ', $example->subject_tags ?? []));
        $score = 0;

        if ($this->hasSexualContent($text)) {
            return -100;
        }

        if ($context === 'family_naturism' && $this->hasUnsafeMinorAnatomy($text)) {
            return -100;
        }

        if ($context === 'adult_naturism' && $this->mentionsMinors($text)) {
            $score -= 20;
        }

        if ($example->content_context === $context) {
            $score += 14;
        } elseif ($context === 'adult_naturism' && $example->content_context === 'family_naturism') {
            $score -= 8;
        } elseif ($context === 'family_naturism' && $example->content_context === 'adult_naturism') {
            $score -= 5;
        } elseif ($example->content_context === 'general') {
            $score += 1;
        }

        if (in_array($use, $example->intended_uses ?? [], true)) {
            $score += 5;
        }

        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                $score += 3;
            }
        }

        if ($context !== 'general' && $this->isExplicitlyNude($text)) {
            $score += 6;
        }

        if ($context !== 'general' && $this->isMixedClothingExample($text)) {
            $score -= 12;
        }

        $detailLevel = $this->classifyBodyDetailLevel($text);
        $score += match ($bodyDetailLevel) {
            'detailed_adult_anatomy' => match ($detailLevel) {
                'detailed_adult_anatomy' => 12,
                'natural_detail' => 5,
                default => -2,
            },
            'natural_detail' => match ($detailLevel) {
                'natural_detail' => 9,
                'detailed_adult_anatomy' => 6,
                default => 1,
            },
            default => match ($detailLevel) {
                'contextual' => 6,
                'natural_detail' => 3,
                default => 0,
            },
        };

        if ($context === 'family_naturism') {
            if ($example->is_family_friendly) {
                $score += 6;
            }
            if ($this->mentionsFamilyOrChildren($text)) {
                $score += 7;
            }
        }

        if ($context === 'adult_naturism' && ! $example->is_family_friendly) {
            $score += 2;
        }

        if ($this->isGroupOrFamilyRequest($keywords) && $this->looksLikeGroupScene($text)) {
            $score += 4;
        }

        return $score;
    }

    private function classifyBodyDetailLevel(string $text): string
    {
        if ($this->containsAny($text, [
            'pubic hair', 'flaccid penis', 'testicles', 'labia', 'vulva', 'nipples',
        ])) {
            return 'detailed_adult_anatomy';
        }

        if ($this->containsAny($text, [
            'breasts', 'chest hair', 'body hair', 'natural curves', 'soft stomach', 'body types', 'curvy body',
        ])) {
            return 'natural_detail';
        }

        return 'contextual';
    }

    private function isExplicitlyNude(string $text): bool
    {
        return $this->containsAny($text, ['nude', 'naked', 'unclothed', 'fully nude', 'completely nude', 'naturist', 'nudist']);
    }

    private function mentionsMinors(string $text): bool
    {
        return $this->containsAny($text, [
            ' child', 'children', ' kid', 'kids', ' boy', 'girl', 'teen', 'teenage', 'minor', 'son', 'daughter', 'grandchild',
        ]);
    }

    private function mentionsFamilyOrChildren(string $text): bool
    {
        return $this->containsAny($text, [
            'family', 'mother', 'father', 'parents', 'children', 'kids', 'grandmother', 'grandfather', 'multi-generational', 'multigenerational',
        ]);
    }

    private function isGroupOrFamilyRequest(Collection $keywords): bool
    {
        return $keywords->contains(fn (string $keyword) => in_array($keyword, [
            'family', 'group', 'friends', 'couple', 'together', 'gathering', 'party', 'community',
        ], true));
    }

    private function looksLikeGroupScene(string $text): bool
    {
        return $this->containsAny($text, [
            ' group', 'couple', 'friends', 'family', 'gathering', 'together', 'adults', 'women', 'men', 'participants',
        ]);
    }

    private function hasUnsafeMinorAnatomy(string $text): bool
    {
        return $this->mentionsMinors($text)
            && $this->containsAny($text, ['pubic hair', 'flaccid penis', 'testicles', 'vulva', 'labia', 'nipple', 'breasts']);
    }

    private function hasSexualContent(string $text): bool
    {
        return $this->containsAny($text, [
            'sexual activity', 'erotic', 'passionate', 'threesome', 'intimate sexual', 'pleasure', 'orgasm', 'penetrat', 'aroused', 'erection',
        ]);
    }

    private function isMixedClothingExample(string $text): bool
    {
        return $this->isExplicitlyNude($text)
            && $this->containsAny($text, [
                'clothed', 'fully dressed', 'wears casual', 'wearing a sweater', 'wearing jeans', 'shirt', 'pants', 'dress', 'bikini', 'swimsuit',
            ]);
    }

    /**
     * @param array<int, string> $needles
     */
    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }
}
