<?php

namespace Database\Seeders;

use App\Models\AiContentPolicy;
use App\Services\Ai\ContentStudio\PromptExampleImporter;
use Illuminate\Database\Seeder;

class AiContentStudioSeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            [
                'key' => 'general-content',
                'name' => 'General Content',
                'applies_to' => 'all',
                'instructions' => 'Keep all content nonsexual, respectful, inclusive, and focused on ordinary life, education, wellness, recreation, relationships, or community. Never generate explicit sexual activity or fetishized framing.',
            ],
            [
                'key' => 'family-naturism',
                'name' => 'Family Naturism',
                'applies_to' => 'family_naturism',
                'instructions' => 'Family naturism is allowed as a nonsexual lifestyle context. Focus on ordinary family activities, relationships, setting, and community. Adult family members may be explicitly described as nude. Minors may be present, but their private areas must never be shown, described, emphasized, or made visible; use distance, angles, towels, water, furniture, foreground objects, or natural positioning.',
            ],
            [
                'key' => 'image-prompt-content_only',
                'name' => 'Content-only Prompt',
                'applies_to' => 'image_prompt',
                'instructions' => 'Output only image content. Exclude camera, lens, lighting recipes, color grading, rendering engines, quality tags, and stylistic production language.',
            ],
            [
                'key' => 'image-prompt-content_composition',
                'name' => 'Content and Composition Prompt',
                'applies_to' => 'image_prompt',
                'instructions' => 'Include scene content and broad composition only. Exclude camera, lens, rendering engines, and quality tags.',
            ],
            [
                'key' => 'image-prompt-full',
                'name' => 'Full Image Prompt',
                'applies_to' => 'image_prompt',
                'instructions' => 'Create a detailed production prompt while keeping the subject matter nonsexual and respectful.',
            ],
        ];

        foreach ($policies as $policy) {
            AiContentPolicy::updateOrCreate(
                ['key' => $policy['key']],
                $policy + ['version' => 2, 'is_enabled' => true],
            );
        }

        $path = database_path('data/ai-prompt-library.json');

        if (file_exists($path) && \App\Models\AiPromptExample::count() === 0) {
            app(PromptExampleImporter::class)->import($path);
        }
    }
}
