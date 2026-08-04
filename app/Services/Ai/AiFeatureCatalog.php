<?php

namespace App\Services\Ai;

class AiFeatureCatalog
{
    public const FEATURES = [
        'blog_analysis' => 'Blog analysis, SEO, and tags',
        'blog_image_prompt' => 'Blog detailed image prompts',
        'image_prompt' => 'Standalone image prompt generator',
    ];

    public static function label(string $feature): string
    {
        return self::FEATURES[$feature] ?? $feature;
    }
}
