<?php

namespace App\Services\Ai\ContentStudio;

use App\Models\AiGeneration;
use App\Services\Ai\Support\AiKeywordExclusionFilter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class BlogContentAssistantService
{
    public function __construct(private AiKeywordExclusionFilter $keywordExclusionFilter) {}

    /** @return array<string, mixed> */
    public function analyze(array $data, ?int $userId = null): array
    {
        $data = $this->resolveOptions($data);

        $generation = AiGeneration::create([
            'feature' => 'blog_content_assistant',
            'status' => 'processing',
            'input_text' => (string) ($data['title'] ?? ''),
            'input_context' => $data,
            'requested_by' => $userId,
            'prompt_template_version' => '3',
        ]);

        try {
            $model = (string) config('ai-assets.providers.ollama.model', 'qwen3-vl:8b');
            $raw = $this->request($this->prompt($data), $model, $data);

            try {
                $result = $this->decode($raw);
            } catch (RuntimeException $decodeException) {
                $repaired = $this->request($this->jsonRepairPrompt($raw), $model, ['description_depth' => 'compact']);
                $result = $this->decode($repaired);
            }

            $generation->update([
                'provider' => 'ollama',
                'model' => $model,
                'status' => 'completed',
                'output_text' => json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'output_data' => $result,
                'error_message' => null,
            ]);

            return $result;
        } catch (Throwable $exception) {
            $generation->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function resolveOptions(array $data): array
    {
        $data['body_detail_level'] = (string) ($data['body_detail_level'] ?? match ($data['content_context'] ?? 'general') {
            'adult_naturism' => 'detailed_adult_anatomy',
            'family_naturism' => 'natural_detail',
            default => 'contextual',
        });
        $data['description_depth'] = (string) ($data['description_depth'] ?? 'expanded');
        $data['character_detail_level'] = (string) ($data['character_detail_level'] ?? match ($data['content_context'] ?? 'general') {
            'adult_naturism' => 'very_detailed',
            'family_naturism' => 'detailed',
            default => 'detailed',
        });
        $data['environment_detail_level'] = (string) ($data['environment_detail_level'] ?? 'detailed');
        $data['describe_every_visible_person'] = filter_var($data['describe_every_visible_person'] ?? true, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        $data['describe_every_visible_person'] = $data['describe_every_visible_person'] ?? true;

        return $data;
    }

    private function prompt(array $data): string
    {
        $plainContent = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($data['content'] ?? ''))) ?? '');
        $plainContent = mb_substr($plainContent, 0, 11000);
        $context = (string) $data['content_context'];
        $bodyDetail = (string) $data['body_detail_level'];

        return <<<PROMPT
You are the Unclad Collection Blog AI Assistant. Analyze the supplied draft and return valid JSON only. Do not use markdown fences or add commentary outside the JSON.

ARTICLE TITLE:
{$data['title']}

CURRENT EXCERPT:
{$data['excerpt']}

ARTICLE CONTENT:
{$plainContent}

CONTENT CONTEXT: {$context}
BODY DETAIL LEVEL FOR IMAGE PROMPTS: {$bodyDetail}
DESCRIPTION DEPTH FOR IMAGE PROMPTS: {$data['description_depth']}
CHARACTER DETAIL LEVEL FOR IMAGE PROMPTS: {$data['character_detail_level']}
ENVIRONMENT DETAIL LEVEL FOR IMAGE PROMPTS: {$data['environment_detail_level']}
DESCRIBE EVERY VISIBLE PERSON IN IMAGE PROMPTS: {$this->yesNo($data['describe_every_visible_person'])}

GENERAL RULES:
- Keep recommendations aligned with the article's actual subject. Do not invent unsupported claims.
- Preserve the author's friendly, confident tone.
- Give useful, specific edits instead of vague advice.
- SEO suggestions should be natural and readable, not keyword stuffed.
- Generate 5-12 concise blog tags that reflect the article's actual topics, activities, setting, audience, and naturism context. Avoid near-duplicates, sentence fragments, overly broad words, and unsupported terms.
- Readability and clarity scores must be integers from 0 to 100, where higher is better.
- Return no more than 2 inline image recommendations unless the article clearly needs more.

IMAGE PROMPT RULES:
{$this->imagePromptRules($data)}

Return this exact JSON object shape:
{
  "summary": "2-4 sentence summary of the article",
  "excerpt": "recommended public excerpt, maximum 320 characters",
  "seo_title": "recommended SEO title, ideally 50-60 characters",
  "seo_description": "recommended SEO description, ideally 140-160 characters",
  "generated_tags": ["concise blog tag"],
  "readability": {
    "score": 0,
    "level": "Excellent|Good|Needs work",
    "strengths": ["specific strength"],
    "improvements": ["specific improvement"]
  },
  "clarity": {
    "score": 0,
    "strengths": ["specific strength"],
    "improvements": ["specific improvement"]
  },
  "publishing_review": {
    "ready": true,
    "missing_items": ["specific missing item"],
    "warnings": ["specific warning"],
    "recommended_actions": ["specific action"]
  },
  "header_image": {
    "concept": "short concept name",
    "prompt": "concise 2-4 sentence scene brief for later detailed expansion",
    "alt_text": "concise accessible alt text",
    "caption": "optional public caption"
  },
  "inline_images": [
    {
      "placement": "after the paragraph or section identified by its topic",
      "purpose": "why the image helps",
      "prompt": "concise 2-4 sentence scene brief for later detailed expansion",
      "alt_text": "accessible alt text",
      "caption": "optional caption"
    }
  ],
  "internal_link_ideas": [
    {
      "anchor_text": "suggested anchor text",
      "target_topic": "related Unclad Collection article, category, collection, or resource to link to",
      "reason": "why the link is useful"
    }
  ]
}
PROMPT;
    }

    private function imagePromptRules(array $data): string
    {
        $lines = [
            'At this stage, choose the strongest visual concepts and write concise scene briefs, not the final expanded production prompts.',
            'Each scene brief should be 2-4 sentences and identify the central adult subject, story moment, setting, emotional meaning, and the most important visible details.',
            'Choose moments that express the article rather than merely illustrating the nearest paragraph literally.',
            'The separate detailed prompt generator will later expand each scene brief through a dedicated scene-planning and prompt-writing process.',
            'Do not spend the JSON response budget trying to write long image prompts here.',
        ];

        if ($data['describe_every_visible_person']) {
            $lines[] = 'When proposing a group scene, identify who the visible people are and how each contributes to the scene.';
        }

        $lines[] = match ($data['content_context']) {
            'adult_naturism' => 'Use adult-only visual concepts. Every depicted person must be age 21 or older. If the article mentions childhood, teenagers, parenting, or children, choose an adult-life moment or an adult protagonist reflecting on the journey. Never propose a visual depicting minors or childhood nudity.',
            'family_naturism' => 'Use wholesome family-naturism concepts. Adult nudity may be explicit and nonsexual. Minors may only appear in ordinary family context, with no anatomy described and private areas naturally obscured.',
            default => 'Do not introduce nudity unless the article requires it. Note meaningful clothing details when relevant.',
        };

        $lines[] = 'Never add sexual activity, arousal, erotic posing, fetish framing, voyeurism, or unsupported provocative details.';

        return collect($lines)->map(fn (string $line) => '- '.$line)->implode("\n");
    }

    private function descriptionDepthRule(string $depth): string
    {
        return match ($depth) {
            'compact' => 'Keep prompts concise but specific.',
            'standard' => 'Provide a solid amount of detail, enough for a useful prompt.',
            'detailed' => 'Provide detailed prompts with well-described people, surroundings, and actions.',
            default => 'Provide expanded, richly detailed prompts with layered description of the people, surroundings, objects, and atmosphere.'
        };
    }

    private function characterDetailRule(string $level, string $context, string $bodyDetailLevel): string
    {
        $base = match ($level) {
            'minimal' => 'Keep person descriptions brief: role, action, and one or two visible details.',
            'standard' => 'Describe people with role, action, approximate age range, build, hair, and a few visible features.',
            'detailed' => 'Describe each visible person with role, action, approximate age range, build, body shape, hair color or hairstyle, expression, and position in the scene.',
            default => 'Describe each visible person in a very detailed way: role, action, approximate age range, body type, build, hair color or hairstyle, expression, pose, placement, and other visible features that help distinguish them.'
        };

        if ($context === 'general') {
            return $base.' If a person is clothed, include what they are wearing when relevant.';
        }

        if ($bodyDetailLevel === 'detailed_adult_anatomy') {
            return $base.' For adults in naturist prompts, include strong but neutral adult body detail in a nonsexual way.';
        }

        if ($bodyDetailLevel === 'natural_detail') {
            return $base.' For adults in naturist prompts, include realistic natural-body detail in a nonsexual way.';
        }

        return $base.' State nudity clearly where appropriate, while keeping body description lighter.';
    }

    private function environmentDetailRule(string $level): string
    {
        return match ($level) {
            'minimal' => 'Keep the surroundings concise.',
            'standard' => 'Describe the main environment and mood.',
            'detailed' => 'Describe the setting with meaningful detail such as room or landscape features, objects, décor, season, weather, or background elements.',
            default => 'Describe the environment richly, including architecture or landscape, décor, surfaces, objects, weather or season, and background details that make the scene feel complete.'
        };
    }

    private function yesNo(bool $value): string
    {
        return $value ? 'yes' : 'no';
    }

    private function request(string $prompt, string $model, array $data): string
    {
        $baseUrl = rtrim((string) config('ai-assets.providers.ollama.base_url'), '/');
        $token = trim((string) config('ai-assets.providers.ollama.token'));

        if ($baseUrl === '' || $token === '') {
            throw new RuntimeException('Ollama is not configured.');
        }

        $numPredict = match ((string) ($data['description_depth'] ?? 'standard')) {
            'compact' => 2600,
            'standard' => 3600,
            'detailed' => 4600,
            default => 5600,
        };

        $payload = [
            'model' => $model,
            'stream' => true,
            'think' => false,
            'keep_alive' => (string) config('ai-assets.providers.ollama.keep_alive', '10m'),
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'format' => 'json',
            'options' => ['temperature' => 0.15, 'num_predict' => $numPredict],
        ];

        $attempts = max(1, (int) config('ai-assets.providers.ollama.retry_times', 1) + 1);
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return $this->stream($baseUrl, $token, $payload);
            } catch (ConnectionException|RuntimeException $exception) {
                $lastException = $exception;

                if ($exception instanceof RuntimeException && ! $this->retryable($exception)) {
                    throw $exception;
                }
            }

            if ($attempt < $attempts) {
                usleep(max(0, (int) config('ai-assets.providers.ollama.retry_sleep_milliseconds', 750)) * 1000);
            }
        }

        throw new RuntimeException('Ollama blog assistant request failed after retrying: '.($lastException?->getMessage() ?? 'unknown transport error'), previous: $lastException);
    }

    /** @param array<string, mixed> $payload */
    private function stream(string $baseUrl, string $token, array $payload): string
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->withOptions(['stream' => true])
            ->connectTimeout((int) config('ai-assets.providers.ollama.connect_timeout_seconds', 15))
            ->timeout((int) config('ai-assets.providers.ollama.timeout_seconds', 300))
            ->post($baseUrl.'/api/chat', $payload);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Ollama request failed: '.trim((string) data_get($response->json(), 'error', $exception->getMessage())), previous: $exception);
        }

        $body = $response->toPsrResponse()->getBody();
        $buffer = '';
        $content = '';
        $thinking = '';
        $sawChunk = false;

        try {
            while (! $body->eof()) {
                $chunk = $body->read(8192);
                if ($chunk === '') {
                    usleep(10000);
                    continue;
                }
                $buffer .= $chunk;

                while (($newline = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $newline));
                    $buffer = substr($buffer, $newline + 1);
                    if ($line !== '') {
                        $sawChunk = true;
                        $this->consumeChunk($line, $content, $thinking);
                    }
                }
            }

            if (trim($buffer) !== '') {
                $sawChunk = true;
                $this->consumeChunk(trim($buffer), $content, $thinking);
            }
        } catch (Throwable $exception) {
            throw new RuntimeException('Ollama streaming connection ended unexpectedly: '.$exception->getMessage(), previous: $exception);
        }

        if (! $sawChunk) {
            throw new RuntimeException('Ollama returned an empty streaming response.');
        }

        if (trim($content) !== '') {
            return trim($content);
        }

        if (trim($thinking) !== '') {
            return trim($thinking);
        }

        throw new RuntimeException('Ollama returned streaming chunks, but neither content nor thinking contained a response.');
    }

    private function consumeChunk(string $line, string &$content, string &$thinking): void
    {
        $json = json_decode($line, true);

        if (! is_array($json)) {
            throw new RuntimeException('Ollama returned an unreadable streaming chunk.');
        }

        if (is_string(data_get($json, 'message.content'))) {
            $content .= (string) data_get($json, 'message.content');
        }

        if (is_string(data_get($json, 'message.thinking'))) {
            $thinking .= (string) data_get($json, 'message.thinking');
        }

        if (is_string($json['response'] ?? null)) {
            $content .= $json['response'];
        }

        if (is_string($json['thinking'] ?? null)) {
            $thinking .= $json['thinking'];
        }

        if (is_string($json['error'] ?? null) && trim($json['error']) !== '') {
            throw new RuntimeException('Ollama request failed: '.trim($json['error']));
        }
    }

    private function jsonRepairPrompt(string $raw): string
    {
        $raw = mb_substr($raw, 0, 30000);

        return <<<PROMPT
Convert the following response into one valid JSON object. Return JSON only, with no markdown fence and no explanation. Preserve all useful content, repair missing commas or quotes, complete truncated arrays with empty arrays when necessary, and use this exact top-level shape:
{
  "summary": "",
  "excerpt": "",
  "seo_title": "",
  "seo_description": "",
  "generated_tags": [],
  "readability": {"score": 0, "level": "Good", "strengths": [], "improvements": []},
  "clarity": {"score": 0, "strengths": [], "improvements": []},
  "publishing_review": {"ready": false, "missing_items": [], "warnings": [], "recommended_actions": []},
  "header_image": {"concept": "", "prompt": "", "alt_text": "", "caption": ""},
  "inline_images": [],
  "internal_link_ideas": []
}

RESPONSE TO REPAIR:
{$raw}
PROMPT;
    }

    /** @return array<string, mixed> */
    private function decode(string $raw): array
    {
        $trimmed = trim($raw);
        $trimmed = preg_replace('/^```(?:json)?\s*/i', '', $trimmed) ?? $trimmed;
        $trimmed = preg_replace('/\s*```$/', '', $trimmed) ?? $trimmed;

        $decoded = json_decode($trimmed, true);
        if (! is_array($decoded)) {
            $start = strpos($trimmed, '{');
            $end = strrpos($trimmed, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $decoded = json_decode(substr($trimmed, $start, $end - $start + 1), true);
            }
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('The AI returned blog suggestions that could not be decoded as JSON.');
        }

        return $this->normalize($decoded);
    }

    /** @return array<string, mixed> */
    private function normalize(array $result): array
    {
        return [
            'summary' => trim((string) ($result['summary'] ?? '')),
            'excerpt' => mb_substr(trim((string) ($result['excerpt'] ?? '')), 0, 320),
            'seo_title' => mb_substr(trim((string) ($result['seo_title'] ?? '')), 0, 255),
            'seo_description' => mb_substr(trim((string) ($result['seo_description'] ?? '')), 0, 500),
            'generated_tags' => array_slice($this->keywordExclusionFilter->filter(
                is_array($result['generated_tags'] ?? null) ? $result['generated_tags'] : [],
            ), 0, 12),
            'readability' => $this->normalizeRating(
                is_array($result['readability'] ?? null) ? $result['readability'] : [],
                true,
            ),
            'clarity' => $this->normalizeRating(
                is_array($result['clarity'] ?? null) ? $result['clarity'] : [],
            ),
            'publishing_review' => is_array($result['publishing_review'] ?? null) ? $result['publishing_review'] : [],
            'header_image' => is_array($result['header_image'] ?? null) ? $result['header_image'] : [],
            'inline_images' => array_values(array_filter(is_array($result['inline_images'] ?? null) ? $result['inline_images'] : [], 'is_array')),
            'internal_link_ideas' => array_values(array_filter(is_array($result['internal_link_ideas'] ?? null) ? $result['internal_link_ideas'] : [], 'is_array')),
        ];
    }

    /**
     * @param array<string, mixed> $rating
     * @return array<string, mixed>
     */
    private function normalizeRating(array $rating, bool $includeLevel = false): array
    {
        $rawScore = $rating['score'] ?? 0;
        $score = is_numeric($rawScore) ? (float) $rawScore : 0.0;

        if ($score > 0 && $score <= 10) {
            $score *= 10;
        }

        $score = (int) round(max(0, min(100, $score)));
        $rating['score'] = $score;
        $rating['strengths'] = array_values(array_filter(
            is_array($rating['strengths'] ?? null) ? $rating['strengths'] : [],
            'is_string',
        ));
        $rating['improvements'] = array_values(array_filter(
            is_array($rating['improvements'] ?? null) ? $rating['improvements'] : [],
            'is_string',
        ));

        if ($includeLevel) {
            $rating['level'] = match (true) {
                $score >= 85 => 'Excellent',
                $score >= 70 => 'Good',
                default => 'Needs work',
            };
        }

        return $rating;
    }

    private function retryable(RuntimeException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'curl error 56')
            || str_contains($message, 'unexpected eof')
            || str_contains($message, 'connection reset')
            || str_contains($message, 'streaming connection ended unexpectedly')
            || str_contains($message, 'empty streaming response');
    }
}
