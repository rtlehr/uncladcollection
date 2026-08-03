<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Services\Ai\ContentStudio\BlogContentAssistantService;
use App\Services\Ai\ContentStudio\BlogImagePromptService;
use App\Services\Ai\Support\AiKeywordExclusionFilter;
use App\Services\BlogTagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class BlogAiAssistantController extends Controller
{
    public function analyze(Request $request, BlogContentAssistantService $assistant): JsonResponse
    {
        $validated = $request->validate([
            'blog_post_id' => ['nullable', 'integer', 'exists:blog_posts,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'content' => ['nullable', 'string'],
            'content_context' => ['required', 'in:general,adult_naturism,family_naturism'],
            'body_detail_level' => ['required', 'in:contextual,natural_detail,detailed_adult_anatomy'],
            'description_depth' => ['required', 'in:compact,standard,detailed,expanded'],
            'character_detail_level' => ['required', 'in:minimal,standard,detailed,very_detailed'],
            'environment_detail_level' => ['required', 'in:minimal,standard,detailed,rich'],
            'describe_every_visible_person' => ['required', 'boolean'],
        ]);

        abort_if(
            trim((string) ($validated['title'] ?? '')) === ''
            && trim(strip_tags((string) ($validated['content'] ?? ''))) === '',
            422,
            'Add a title or article content before running the AI assistant.',
        );

        try {
            $result = $assistant->analyze($validated, $request->user()?->id);
            $settings = $this->settingsFrom($validated);
            $analyzedAt = now();
            $saved = false;

            if (! empty($validated['blog_post_id'])) {
                $blogPost = BlogPost::query()->findOrFail($validated['blog_post_id']);
                $blogPost->forceFill([
                    'ai_analysis' => $result,
                    'ai_analysis_settings' => $settings,
                    'ai_analyzed_at' => $analyzedAt,
                ])->save();
                $saved = true;
            }

            return response()->json([
                'result' => $result,
                'settings' => $settings,
                'saved' => $saved,
                'analyzed_at' => $analyzedAt->toIso8601String(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Blog AI Assistant analysis failed.', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'message' => 'The Blog AI Assistant could not analyze this draft: '.$exception->getMessage(),
            ], 500);
        }
    }

    public function generateImagePrompt(
        Request $request,
        BlogImagePromptService $promptService,
    ): JsonResponse {
        $validated = $request->validate([
            'blog_post_id' => ['nullable', 'integer', 'exists:blog_posts,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'image_type' => ['required', 'in:header,inline'],
            'inline_index' => ['nullable', 'integer', 'min:0', 'max:20'],
            'concept' => ['required', 'string', 'max:2000'],
            'placement' => ['nullable', 'string', 'max:2000'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'current_prompt' => ['nullable', 'string', 'max:20000'],
            'content_context' => ['required', 'in:general,adult_naturism,family_naturism'],
            'body_detail_level' => ['required', 'in:contextual,natural_detail,detailed_adult_anatomy'],
            'description_depth' => ['required', 'in:compact,standard,detailed,expanded'],
            'character_detail_level' => ['required', 'in:minimal,standard,detailed,very_detailed'],
            'environment_detail_level' => ['required', 'in:minimal,standard,detailed,rich'],
            'describe_every_visible_person' => ['required', 'boolean'],
        ]);

        abort_if(
            trim((string) ($validated['title'] ?? '')) === ''
            && trim(strip_tags((string) ($validated['content'] ?? ''))) === '',
            422,
            'Add a title or article content before generating an image prompt.',
        );

        try {
            $generated = $promptService->generate($validated, $request->user()?->id);
            $saved = false;
            $analysis = null;
            $analyzedAt = now();

            if (! empty($validated['blog_post_id'])) {
                $blogPost = BlogPost::query()->findOrFail($validated['blog_post_id']);
                $analysis = is_array($blogPost->ai_analysis) ? $blogPost->ai_analysis : [];

                if ($validated['image_type'] === 'header') {
                    $analysis['header_image'] = is_array($analysis['header_image'] ?? null)
                        ? $analysis['header_image']
                        : [];
                    $analysis['header_image']['prompt'] = $generated['prompt'];
                    $analysis['header_image']['prompt_generated'] = true;
                } else {
                    $index = (int) ($validated['inline_index'] ?? 0);
                    $analysis['inline_images'] = is_array($analysis['inline_images'] ?? null)
                        ? array_values($analysis['inline_images'])
                        : [];

                    if (isset($analysis['inline_images'][$index]) && is_array($analysis['inline_images'][$index])) {
                        $analysis['inline_images'][$index]['prompt'] = $generated['prompt'];
                        $analysis['inline_images'][$index]['prompt_generated'] = true;
                    }
                }

                $blogPost->forceFill([
                    'ai_analysis' => $analysis,
                    'ai_analysis_settings' => $this->settingsFrom($validated),
                    'ai_analyzed_at' => $analyzedAt,
                ])->save();
                $saved = true;
            }

            return response()->json([
                'prompt' => $generated['prompt'],
                'scene_plan' => $generated['scene_plan'],
                'saved' => $saved,
                'analysis' => $analysis,
                'analyzed_at' => $analyzedAt->toIso8601String(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Blog detailed image prompt generation failed.', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'message' => 'The detailed image prompt could not be generated: '.$exception->getMessage(),
            ], 500);
        }
    }

    public function resolveTags(
        Request $request,
        BlogTagService $tagService,
        AiKeywordExclusionFilter $keywordFilter,
    ): JsonResponse {
        $validated = $request->validate([
            'names' => ['required', 'array', 'min:1', 'max:50'],
            'names.*' => ['string', 'max:100'],
        ]);

        $names = $keywordFilter->filter($validated['names']);
        $tags = $tagService->resolveNames($names);

        return response()->json([
            'tags' => $tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ])->values(),
        ]);
    }

    /** @return array<string, mixed> */
    private function settingsFrom(array $validated): array
    {
        return [
            'content_context' => $validated['content_context'],
            'body_detail_level' => $validated['body_detail_level'],
            'description_depth' => $validated['description_depth'],
            'character_detail_level' => $validated['character_detail_level'],
            'environment_detail_level' => $validated['environment_detail_level'],
            'describe_every_visible_person' => (bool) $validated['describe_every_visible_person'],
        ];
    }
}
