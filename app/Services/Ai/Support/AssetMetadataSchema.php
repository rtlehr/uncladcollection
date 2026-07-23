<?php

namespace App\Services\Ai\Support;

use RuntimeException;

final class AssetMetadataSchema
{
    /** @return array<string, mixed> */
    public static function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'alt_text' => ['type' => 'string'],
                'seo_title' => ['type' => 'string'],
                'seo_description' => ['type' => 'string'],
                'keywords' => ['type' => 'array', 'items' => ['type' => 'string']],
                'objects' => ['type' => 'array', 'items' => ['type' => 'string']],
                'scene' => ['type' => 'string'],
                'composition' => ['type' => 'string'],
                'relationship_suggestions' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'required' => [
                'title', 'description', 'alt_text', 'seo_title', 'seo_description',
                'keywords', 'objects', 'scene', 'composition', 'relationship_suggestions',
            ],
        ];
    }

    public static function prompt(array $context = []): string
    {
        $prompt = <<<'PROMPT'
Analyze this marketplace asset preview for cataloging. The administrator has confirmed that every visible person is a consenting adult and that the content is respectful and non-sexual.

Return factual, neutral stock-marketplace metadata. Do not identify people. Do not infer ethnicity, religion, health, disability, sexual orientation, political beliefs, or other sensitive traits. Do not describe intimate anatomy in detail. Nudity, when present, should be described neutrally using terms such as naturist, nudist, nude lifestyle, or clothing-optional only when visually relevant.

Return one JSON object only, with no Markdown and no commentary. Create: a concise title, customer-facing description, accessible alt text, SEO title, SEO description, 15-25 useful keywords, general visible objects, scene/setting, composition, and suggested relationship themes for finding related assets.
PROMPT;

        if (is_string($context['title'] ?? null) && trim($context['title']) !== '') {
            $prompt .= "\nExisting title for context only: ".trim($context['title']);
        }

        return $prompt;
    }

    /** @param array<string, mixed> $suggestions */
    public static function normalize(array $suggestions): array
    {
        $requiredStrings = ['title', 'description', 'alt_text', 'seo_title', 'seo_description', 'scene', 'composition'];
        $requiredArrays = ['keywords', 'objects', 'relationship_suggestions'];

        foreach ($requiredStrings as $field) {
            if (! is_string($suggestions[$field] ?? null) || trim($suggestions[$field]) === '') {
                throw new RuntimeException("The AI response is missing a valid {$field} value.");
            }
            $suggestions[$field] = trim($suggestions[$field]);
        }

        foreach ($requiredArrays as $field) {
            if (! is_array($suggestions[$field] ?? null)) {
                throw new RuntimeException("The AI response is missing a valid {$field} list.");
            }

            $suggestions[$field] = array_values(array_unique(array_filter(array_map(
                static fn (mixed $value): string => is_string($value) ? trim($value) : '',
                $suggestions[$field],
            ))));
        }

        return $suggestions;
    }
}
