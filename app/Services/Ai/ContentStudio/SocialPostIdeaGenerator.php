<?php

namespace App\Services\Ai\ContentStudio;

use App\Models\AiGeneration;
use App\Models\SocialPostAiSuggestion;
use App\Services\Ai\AiTextGateway;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SocialPostIdeaGenerator
{
    public function __construct(private AiTextGateway $gateway) {}

    /**
     * @param array<int,array{id?:int,name:string,category?:string|null,prompt_text:string}> $promptAdditions
     * @return array{generation:AiGeneration,suggestions:Collection<int,SocialPostAiSuggestion>}
     */
    public function generate(
        string $subject,
        int $count,
        ?int $userId = null,
        ?string $campaign = null,
        array $promptAdditions = [],
    ): array {
        $promptAdditions = $this->normalizePromptAdditions($promptAdditions);

        return $this->runGeneration(
            subject: $subject,
            count: $count,
            userId: $userId,
            campaign: $this->cleanCampaign($campaign),
            promptAdditions: $promptAdditions,
            prompt: $this->batchPrompt($subject, $count, $promptAdditions),
            context: [
                'count' => $count,
                'mode' => 'batch',
                'campaign' => $this->cleanCampaign($campaign),
                'prompt_additions' => $promptAdditions,
            ],
        );
    }

    /** @return array{generation:AiGeneration,suggestions:Collection<int,SocialPostAiSuggestion>} */
    public function regenerate(SocialPostAiSuggestion $source, ?int $userId = null): array
    {
        $promptAdditions = $this->normalizePromptAdditions($source->prompt_additions ?? []);

        return $this->runGeneration(
            subject: $source->subject,
            count: 1,
            userId: $userId,
            campaign: $source->campaign,
            promptAdditions: $promptAdditions,
            prompt: $this->regeneratePrompt($source, $promptAdditions),
            context: [
                'count' => 1,
                'mode' => 'regenerate',
                'campaign' => $source->campaign,
                'source_suggestion_id' => $source->id,
                'prompt_additions' => $promptAdditions,
            ],
            sourceSuggestionId: $source->id,
        );
    }

    /** @return array{generation:AiGeneration,suggestions:Collection<int,SocialPostAiSuggestion>} */
    public function generateSimilar(SocialPostAiSuggestion $source, int $count, ?int $userId = null): array
    {
        $promptAdditions = $this->normalizePromptAdditions($source->prompt_additions ?? []);

        return $this->runGeneration(
            subject: $source->subject,
            count: $count,
            userId: $userId,
            campaign: $source->campaign,
            promptAdditions: $promptAdditions,
            prompt: $this->similarPrompt($source, $count, $promptAdditions),
            context: [
                'count' => $count,
                'mode' => 'similar',
                'campaign' => $source->campaign,
                'source_suggestion_id' => $source->id,
                'prompt_additions' => $promptAdditions,
            ],
            sourceSuggestionId: $source->id,
        );
    }

    /**
     * @param array<string,mixed> $context
     * @param array<int,array{id?:int,name:string,category?:string|null,prompt_text:string}> $promptAdditions
     * @return array{generation:AiGeneration,suggestions:Collection<int,SocialPostAiSuggestion>}
     */
    private function runGeneration(
        string $subject,
        int $count,
        ?int $userId,
        ?string $campaign,
        array $promptAdditions,
        string $prompt,
        array $context,
        ?int $sourceSuggestionId = null,
    ): array {
        $generation = AiGeneration::create([
            'feature' => 'social_post_generation',
            'status' => 'processing',
            'input_text' => $subject,
            'input_context' => $context,
            'requested_by' => $userId,
            'prompt_template_version' => '3',
        ]);

        try {
            $response = $this->gateway->generate(
                'social_post_generation',
                $prompt,
                ['json' => true, 'temperature' => 0.78, 'max_tokens' => max(1800, $count * 700)],
            );

            $decoded = $this->decode($response['content']);
            $posts = collect($decoded['posts'] ?? [])->take($count)->values();

            if ($posts->count() !== $count) {
                throw new RuntimeException("The AI returned {$posts->count()} usable post ideas; {$count} were requested. Please generate again.");
            }

            $suggestions = DB::transaction(function () use ($generation, $posts, $subject, $campaign, $promptAdditions, $userId, $response, $sourceSuggestionId) {
                $generation->update([
                    'provider' => $response['provider'] ?? null,
                    'model' => $response['model'] ?? null,
                    'status' => 'completed',
                    'output_text' => $response['content'],
                    'output_data' => ['posts' => $posts->all()],
                    'error_message' => null,
                ]);

                return $posts->map(function (array $post, int $index) use ($generation, $subject, $campaign, $promptAdditions, $userId, $sourceSuggestionId) {
                    $text = trim((string) ($post['text'] ?? ''));
                    $image = trim((string) ($post['image_suggestion'] ?? ''));
                    $hashtags = collect($post['hashtags'] ?? [])
                        ->map(fn ($tag) => ltrim(trim((string) $tag), '#'))
                        ->filter()
                        ->unique()
                        ->values()
                        ->take(8)
                        ->all();

                    if ($text === '') {
                        throw new RuntimeException('The AI returned a post idea with no post text.');
                    }

                    return SocialPostAiSuggestion::create([
                        'ai_generation_id' => $generation->id,
                        'requested_by' => $userId,
                        'subject' => $subject,
                        'campaign' => $campaign,
                        'prompt_additions' => $promptAdditions !== [] ? $promptAdditions : null,
                        'position' => $index + 1,
                        'text' => $text,
                        'hashtags' => $hashtags,
                        'image_suggestion' => $image !== '' ? $image : null,
                        'status' => 'pending',
                        'source_suggestion_id' => $sourceSuggestionId,
                    ]);
                });
            });

            return ['generation' => $generation->fresh(), 'suggestions' => $suggestions];
        } catch (Throwable $exception) {
            $generation->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /** @param array<int,array{id?:int,name:string,category?:string|null,prompt_text:string}> $promptAdditions */
    private function batchPrompt(string $subject, int $count, array $promptAdditions): string
    {
        return $this->basePrompt($subject, $count, $promptAdditions).<<<PROMPT

Create a fresh batch. Vary the angle, opening, phrasing, and call to action so the posts do not feel repetitive.
PROMPT;
    }

    /** @param array<int,array{id?:int,name:string,category?:string|null,prompt_text:string}> $promptAdditions */
    private function regeneratePrompt(SocialPostAiSuggestion $source, array $promptAdditions): string
    {
        $original = $source->finalText();
        $image = $source->image_suggestion ?: 'No image concept was supplied.';

        return $this->basePrompt($source->subject, 1, $promptAdditions).<<<PROMPT

This is a replacement for a suggestion the editor rejected. Do NOT simply paraphrase it. Create a meaningfully different angle, hook, structure, and image concept while staying on the same subject and honoring the same selected prompt additions.

REJECTED SUGGESTION:
{$original}

REJECTED IMAGE CONCEPT:
{$image}
PROMPT;
    }

    /** @param array<int,array{id?:int,name:string,category?:string|null,prompt_text:string}> $promptAdditions */
    private function similarPrompt(SocialPostAiSuggestion $source, int $count, array $promptAdditions): string
    {
        $reference = $source->finalText();
        $image = $source->image_suggestion ?: 'No image concept was supplied.';

        return $this->basePrompt($source->subject, $count, $promptAdditions).<<<PROMPT

The editor liked the reference post below. Create {$count} new posts that capture what works about its theme/tone/approach without copying its wording. Each new post must stand on its own and should explore a distinct angle so the results are not near-duplicates. Honor the same selected prompt additions as the reference suggestion.

REFERENCE POST:
{$reference}

REFERENCE IMAGE CONCEPT:
{$image}
PROMPT;
    }

    /** @param array<int,array{id?:int,name:string,category?:string|null,prompt_text:string}> $promptAdditions */
    private function basePrompt(string $subject, int $count, array $promptAdditions): string
    {
        $additionBlock = $this->promptAdditionBlock($promptAdditions);

        return <<<PROMPT
You are the social media writing assistant for Unclad Collection. Create exactly {$count} distinct X/Twitter post ideas about the subject below.

SUBJECT / TOPIC:
{$subject}
{$additionBlock}

CORE RULES:
- Return valid JSON only. No markdown fences and no commentary outside the JSON.
- Create exactly {$count} posts.
- Every post must directly relate to the supplied Subject / Topic. The subject may be about nudism/naturism or any other topic.
- The optional prompt additions are editor-selected context or writing directions. Apply only the additions shown above; do not assume unselected library entries.
- If a selected addition is contextual rather than promotional, use it for accuracy without forcing that context into every post.
- Write polished, natural social copy. Avoid generic AI phrasing, clickbait, and unsupported factual claims.
- The connected X account may support long-form posts, so do not enforce a 280-character limit. Prefer concise social writing unless the subject benefits from a longer post.
- Put hashtags in the hashtags array, not inside the text field.
- Give each post 2-6 useful hashtags. Do not use near-duplicate hashtags.
- Provide one suggested image concept for each post. The image suggestion is guidance for a human choosing or creating media; do not claim an image exists.
- If the subject involves nudism or naturism, keep the tone positive, respectful, nonsexual, body-positive, and community-oriented unless the subject explicitly asks for another legitimate tone.
- Do not include minors in suggested nude/naturist imagery.
- The JSON structure and safety rules in this prompt take priority if a prompt addition conflicts with them.

Return exactly this JSON shape:
{
  "posts": [
    {
      "text": "finished post copy without hashtags",
      "hashtags": ["HashtagOne", "HashtagTwo"],
      "image_suggestion": "specific visual concept that would complement this post"
    }
  ]
}
PROMPT;
    }

    /** @param array<int,array{id?:int,name:string,category?:string|null,prompt_text:string}> $promptAdditions */
    private function promptAdditionBlock(array $promptAdditions): string
    {
        if ($promptAdditions === []) {
            return "\nOPTIONAL PROMPT ADDITIONS SELECTED BY EDITOR:\nNone. Use only the Subject / Topic and the core generator rules.";
        }

        $lines = ["\nOPTIONAL PROMPT ADDITIONS SELECTED BY EDITOR:"];

        foreach ($promptAdditions as $index => $addition) {
            $number = $index + 1;
            $category = trim((string) ($addition['category'] ?? ''));
            $label = trim((string) ($addition['name'] ?? "Addition {$number}"));
            $heading = $category !== '' ? "{$label} ({$category})" : $label;
            $text = trim((string) ($addition['prompt_text'] ?? ''));
            $lines[] = "{$number}. {$heading}:\n{$text}";
        }

        return implode("\n\n", $lines);
    }

    /**
     * @param array<int,mixed> $promptAdditions
     * @return array<int,array{id?:int,name:string,category:?string,prompt_text:string}>
     */
    private function normalizePromptAdditions(array $promptAdditions): array
    {
        return collect($promptAdditions)
            ->map(function ($addition): ?array {
                if (! is_array($addition)) {
                    return null;
                }

                $name = trim((string) ($addition['name'] ?? ''));
                $text = trim((string) ($addition['prompt_text'] ?? ''));

                if ($name === '' || $text === '') {
                    return null;
                }

                $normalized = [
                    'name' => $name,
                    'category' => ($category = trim((string) ($addition['category'] ?? ''))) !== '' ? $category : null,
                    'prompt_text' => $text,
                ];

                if (isset($addition['id']) && is_numeric($addition['id'])) {
                    $normalized['id'] = (int) $addition['id'];
                }

                return $normalized;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function cleanCampaign(?string $campaign): ?string
    {
        $campaign = trim((string) $campaign);
        return $campaign !== '' ? $campaign : null;
    }

    /** @return array<string,mixed> */
    private function decode(string $raw): array
    {
        $clean = trim($raw);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*```$/', '', $clean) ?? $clean;
        $decoded = json_decode($clean, true);

        if (! is_array($decoded) || ! isset($decoded['posts']) || ! is_array($decoded['posts'])) {
            throw new RuntimeException('The AI response was not valid social-post JSON. Please generate again.');
        }

        return $decoded;
    }
}
