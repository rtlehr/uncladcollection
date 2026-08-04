<?php

namespace App\Services\Ai\ContentStudio;

use App\Models\AiGeneration;
use App\Services\Ai\AiTextGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class BlogImagePromptService
{
    private array $lastAiMeta = [];

    public function __construct(private AiTextGateway $aiGateway) {}
    /** @return array{prompt:string,scene_plan:array<string,mixed>} */
    public function generate(array $data, ?int $userId = null): array
    {
        $data = $this->resolveOptions($data);

        $generation = AiGeneration::create([
            'feature' => 'blog_image_prompt',
            'status' => 'processing',
            'input_text' => (string) ($data['concept'] ?? ''),
            'input_context' => $data,
            'requested_by' => $userId,
            'prompt_template_version' => '2',
        ]);

        try {
            $model = '';
            $scenePlanRaw = $this->request(
                $this->scenePlanPrompt($data),
                $model,
                true,
                1600,
            );
            $scenePlan = $this->decodeJsonObject($scenePlanRaw);

            // Long single-response generations are vulnerable to reverse-proxy
            // SSL timeouts. Generate three focused, shorter sections instead,
            // then assemble them locally into one detailed prompt.
            $sections = $this->generatePromptSections($data, $scenePlan, $model);
            $prompt = $this->assemblePrompt($sections);

            if ($prompt === '') {
                throw new RuntimeException('The AI returned an empty detailed image prompt.');
            }

            $generation->update([
                'provider' => $this->lastAiMeta['provider'] ?? 'unknown',
                'model' => $this->lastAiMeta['model'] ?? $model,
                'status' => 'completed',
                'output_text' => $prompt,
                'output_data' => [
                    'scene_plan' => $scenePlan,
                    'final_validation_issues' => $this->detectIssues($prompt, $data),
                ],
                'error_message' => null,
            ]);

            return [
                'prompt' => $prompt,
                'scene_plan' => $scenePlan,
            ];
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
        $data['description_depth'] = (string) ($data['description_depth'] ?? 'expanded');
        $data['character_detail_level'] = (string) ($data['character_detail_level'] ?? 'very_detailed');
        $data['environment_detail_level'] = (string) ($data['environment_detail_level'] ?? 'rich');
        $data['body_detail_level'] = (string) ($data['body_detail_level'] ?? 'detailed_adult_anatomy');
        $data['describe_every_visible_person'] = filter_var(
            $data['describe_every_visible_person'] ?? true,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        ) ?? true;

        return $data;
    }

    private function scenePlanPrompt(array $data): string
    {
        $article = $this->plainArticle($data);
        $currentPrompt = trim((string) ($data['current_prompt'] ?? ''));
        $currentPromptSection = $currentPrompt !== ''
            ? "CURRENT DRAFT PROMPT TO IMPROVE:\n{$currentPrompt}\n"
            : '';

        return <<<PROMPT
You are planning one detailed image for an Unclad Collection blog article. Return valid JSON only, with no markdown or commentary. Work directly and do not output reasoning. /no_think

IMAGE TYPE: {$data['image_type']}
ARTICLE TITLE: {$data['title']}
IMAGE CONCEPT: {$data['concept']}
PLACEMENT: {$data['placement']}
PURPOSE: {$data['purpose']}
CONTENT CONTEXT: {$data['content_context']}
BODY DETAIL LEVEL: {$data['body_detail_level']}
CHARACTER DETAIL LEVEL: {$data['character_detail_level']}
ENVIRONMENT DETAIL LEVEL: {$data['environment_detail_level']}
DESCRIBE EVERY VISIBLE PERSON: {$this->yesNo($data['describe_every_visible_person'])}

{$currentPromptSection}
ARTICLE:
{$article}

PLANNING RULES:
- Choose one visually strong moment that expresses the article's meaning, not merely a literal summary.
- Keep every detail supported by the article or a reasonable visual interpretation of its themes.
- Give each visible person a distinct role, age range, appearance, pose, action, expression, and position.
- Describe the setting as a complete lived-in environment with meaningful objects and background details.
- For adult_naturism, every depicted person must be an adult age 21 or older. Never depict children, teenagers, childhood nudity, or minors. If the article discusses childhood or parenting, choose an adult-life scene or show the adult protagonist reflecting on her journey.
- For adult_naturism, visible adults may be nude in a neutral, nonsexual way. Never add arousal, sexual activity, erotic framing, fetish language, or voyeurism.
- For family_naturism, adult nudity may be clear and nonsexual; minors may only appear in ordinary family context with no anatomy described and private areas naturally obscured.
- For general content, describe clothing precisely when people are clothed.
- Avoid generic phrases such as "a woman in a room" or "a family together" without concrete visual detail.

Return this exact structure:
{
  "visual_story": "the emotional story the image communicates",
  "composition": "broad arrangement and focal emphasis",
  "subjects": [
    {
      "role": "person's role in the story",
      "age_range": "adult age range",
      "appearance": "body type, build, hair, and distinguishing visible features",
      "nudity_or_clothing": "neutral nudity detail or precise clothing",
      "pose_and_action": "what the person is doing",
      "expression": "visible emotional state",
      "position": "where the person is placed in the scene"
    }
  ],
  "environment": {
    "location": "specific place",
    "architecture_or_landscape": "major structural or natural elements",
    "objects_and_decor": ["meaningful visible object"],
    "background_details": ["supporting detail"],
    "time_weather_season": "relevant time, weather, or season",
    "light_and_atmosphere": "natural scene atmosphere"
  },
  "story_symbols": ["subtle visual element that reinforces the article"],
  "must_avoid": ["specific unwanted element"]
}
PROMPT;
    }

    /**
     * Generate the final prompt as several short requests. This avoids keeping
     * one HTTPS request open while Qwen produces a 300-500 word response.
     *
     * @param array<string, mixed> $scenePlan
     * @return array{subjects:string,environment:string,atmosphere:string}
     */
    private function generatePromptSections(array $data, array $scenePlan, string $model): array
    {
        $sections = [];

        foreach ([
            'subjects' => 700,
            'environment' => 600,
            'atmosphere' => 450,
        ] as $section => $numPredict) {
            $sections[$section] = $this->sanitizePrompt($this->request(
                $this->sectionPrompt($data, $scenePlan, $section),
                $model,
                false,
                $numPredict,
            ));

            if ($sections[$section] === '') {
                throw new RuntimeException("The AI returned an empty {$section} section for the detailed image prompt.");
            }
        }

        return $sections;
    }

    /** @param array<string, mixed> $scenePlan */
    private function sectionPrompt(array $data, array $scenePlan, string $section): string
    {
        $planJson = json_encode($scenePlan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $sectionRule = match ($section) {
            'subjects' => 'Write only the opening and people portion. Begin with the central story moment. Describe every visible adult individually with approximate age range, build, hair, expression, pose, action, position, and neutral nudity or precise clothing. Aim for 120-190 words.',
            'environment' => 'Write only the surroundings portion. Describe the room or landscape, architecture, surfaces, furniture or natural elements, meaningful objects, decor, season or weather, and background details. Aim for 100-170 words.',
            default => 'Write only the atmosphere and emotional-purpose portion. Describe light, mood, visual symbolism, and how the image communicates the article themes. For a header, mention visually calm space without discussing typography. Aim for 70-120 words.',
        };

        return <<<PROMPT
/no_think
Write one section of a production-ready Unclad Collection image prompt. Return only finished prompt prose. Do not include a title, label, bullet list, markdown, explanation, analysis, or reasoning.

ARTICLE TITLE: {$data['title']}
IMAGE TYPE: {$data['image_type']}
IMAGE CONCEPT: {$data['concept']}
CONTENT CONTEXT: {$data['content_context']}
BODY DETAIL LEVEL: {$data['body_detail_level']}
CHARACTER DETAIL LEVEL: {$data['character_detail_level']}
ENVIRONMENT DETAIL LEVEL: {$data['environment_detail_level']}

SCENE PLAN:
{$planJson}

SECTION INSTRUCTION:
{$sectionRule}

RULES:
- Treat the scene plan as authoritative and do not invent additional people.
- For adult_naturism, every visible person must be an adult age 21 or older.
- Never depict minors, teenagers, childhood scenes, or childhood nudity in adult_naturism prompts.
- Keep nudity explicit, ordinary, respectful, and nonsexual.
- Never include erotic posing, arousal, sexual activity, voyeurism, fetish framing, or suggestive emphasis.
- Avoid camera specifications, rendering terminology, quality-tag spam, and repeated adjectives.
PROMPT;
    }

    /**
     * @param array{subjects:string,environment:string,atmosphere:string} $sections
     */
    private function assemblePrompt(array $sections): string
    {
        return trim(implode("

", array_filter([
            trim($sections['subjects'] ?? ''),
            trim($sections['environment'] ?? ''),
            trim($sections['atmosphere'] ?? ''),
        ])));
    }

    /** @param array<string, mixed> $scenePlan */
    private function finalPrompt(array $data, array $scenePlan): string
    {
        $article = $this->plainArticle($data);
        $planJson = json_encode($scenePlan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $lengthRule = $this->lengthRule($data['description_depth']);

        return <<<PROMPT
Write one polished, production-ready image prompt for Unclad Collection. Return only the prompt, with no title, labels, bullets, markdown, explanation, or reasoning. /no_think

ARTICLE TITLE: {$data['title']}
IMAGE TYPE: {$data['image_type']}
IMAGE CONCEPT: {$data['concept']}
PLACEMENT: {$data['placement']}
PURPOSE: {$data['purpose']}
CONTENT CONTEXT: {$data['content_context']}
BODY DETAIL LEVEL: {$data['body_detail_level']}
CHARACTER DETAIL LEVEL: {$data['character_detail_level']}
ENVIRONMENT DETAIL LEVEL: {$data['environment_detail_level']}
DESCRIBE EVERY VISIBLE PERSON: {$this->yesNo($data['describe_every_visible_person'])}

SCENE PLAN:
{$planJson}

ARTICLE CONTEXT:
{$article}

FINAL PROMPT REQUIREMENTS:
- {$lengthRule}
- Write as a cohesive narrative image prompt, not a checklist.
- Start with the central subject and story moment, then describe each visible person individually, then the surroundings, meaningful objects, atmosphere, and emotional intent.
- Give every visible adult an approximate age range, body type or build, hair color or hairstyle, expression, pose, action, and location in the scene.
- When adults are nude, say so explicitly and describe their bodies naturally and nonsexually according to the selected body detail level.
- When people are clothed, specify garment type, color, and relevant accessories.
- Make the environment richly specific: architecture or landscape, surfaces, furniture or natural elements, objects, décor, season or weather, background details, and light.
- Connect the visual details to the article's emotional meaning: authenticity, comfort, acceptance, identity, resilience, belonging, or another theme supported by the article.
- For adult_naturism, all depicted people must be adults age 21 or older. Do not depict minors, childhood scenes, teenagers, or children, even if the article mentions them.
- Keep nudity ordinary, respectful, and nonsexual. Never include erotic posing, arousal, sexual activity, voyeurism, fetish framing, or suggestive emphasis.
- Do not use vague filler, repeated adjectives, quality-tag spam, camera specifications, or rendering-engine terminology.
- For a header image, use a clear focal subject and preserve some visually calm space suitable for a title without mentioning typography.
PROMPT;
    }

    /** @param array<string, mixed> $scenePlan @param array<int, string> $issues */
    private function repairPrompt(array $data, array $scenePlan, string $draft, array $issues): string
    {
        $planJson = json_encode($scenePlan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Rewrite the image prompt below so it fully satisfies the requested detail and safety requirements. Return only the corrected final prompt with no title, labels, bullets, markdown, explanation, or reasoning. /no_think

CONTENT CONTEXT: {$data['content_context']}
DESCRIPTION DEPTH: {$data['description_depth']}
CHARACTER DETAIL LEVEL: {$data['character_detail_level']}
ENVIRONMENT DETAIL LEVEL: {$data['environment_detail_level']}
BODY DETAIL LEVEL: {$data['body_detail_level']}

PROBLEMS TO FIX:
- {$this->implodeIssues($issues)}

SCENE PLAN:
{$planJson}

CURRENT DRAFT:
{$draft}

REQUIREMENTS:
- {$this->lengthRule($data['description_depth'])}
- Describe each visible adult individually with age range, build, hair, expression, pose, action, and placement.
- Describe the environment, objects, background, atmosphere, and emotional story in rich concrete detail.
- For adult_naturism, depict adults age 21 or older only and never depict minors or childhood nudity.
- Keep all nudity neutral, ordinary, and nonsexual.
PROMPT;
    }

    /** @return array<int, string> */
    private function detectIssues(string $prompt, array $data): array
    {
        $issues = [];
        $text = mb_strtolower($prompt);
        $wordCount = str_word_count(strip_tags($prompt));

        if ($this->containsAny($text, ['<think>', 'the user wants me to', 'let me break this down', 'i need to understand', 'better approach:', 'critical nuance:'])) {
            $issues[] = 'The response contains internal model reasoning instead of a finished image prompt.';
        }

        $minimumWords = match ($data['description_depth']) {
            'compact' => 65,
            'standard' => 110,
            'detailed' => 180,
            default => 260,
        };

        if ($wordCount < $minimumWords) {
            $issues[] = "The prompt is too short for the selected depth; it has about {$wordCount} words and should have at least {$minimumWords}.";
        }

        if ($data['content_context'] === 'adult_naturism') {
            if (! $this->containsAny($text, ['nude', 'naked', 'unclothed', 'naturist', 'nudist'])) {
                $issues[] = 'The prompt does not clearly establish adult naturist nudity.';
            }

            if ($this->containsAny($text, ['child', 'children', 'kid', 'kids', 'teen', 'teenage', 'minor', 'boy', 'girl'])) {
                $issues[] = 'The prompt appears to depict or mention minors even though adult naturism was selected.';
            }
        }

        if (in_array($data['character_detail_level'], ['detailed', 'very_detailed'], true)
            && ! $this->containsAny($text, ['hair', 'hairstyle', 'build', 'body type', 'curvy', 'slim', 'muscular', 'soft build', 'expression', 'posture', 'pose'])) {
            $issues[] = 'The prompt lacks enough person-by-person appearance and pose detail.';
        }

        if (in_array($data['environment_detail_level'], ['detailed', 'rich'], true)
            && ! $this->containsAny($text, ['background', 'room', 'home', 'studio', 'window', 'furniture', 'decor', 'landscape', 'walls', 'floor', 'shelves', 'objects', 'weather', 'season'])) {
            $issues[] = 'The prompt lacks enough concrete environmental detail.';
        }

        return $issues;
    }

    private function plainArticle(array $data): string
    {
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($data['content'] ?? ''))) ?? '');

        return mb_substr($plain, 0, 14000);
    }

    private function sanitizePrompt(string $output): string
    {
        $output = trim($output);
        $output = preg_replace('/^```(?:text)?\s*/i', '', $output) ?? $output;
        $output = preg_replace('/\s*```$/', '', $output) ?? $output;

        // Never expose or persist model chain-of-thought. Remove complete think
        // blocks, and reject a response that consists only of leaked reasoning.
        $output = preg_replace('/<think>.*?<\/think>/is', '', $output) ?? $output;
        if (preg_match('/^\s*<think>/i', $output) === 1) {
            return '';
        }

        $output = preg_replace('/^(here is|image prompt:|prompt:|final prompt:)\s*/i', '', $output) ?: $output;

        return trim($output);
    }

    /** @return array<string, mixed> */
    private function decodeJsonObject(string $raw): array
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
            throw new RuntimeException('The AI scene plan could not be decoded as JSON.');
        }

        return $decoded;
    }

    private function request(string $prompt, string $model, bool $jsonMode, int $numPredict): string
    {
        $result = $this->aiGateway->generate('blog_image_prompt', $prompt, [
            'json' => $jsonMode,
            'temperature' => $jsonMode ? 0.15 : 0.35,
            'max_tokens' => $numPredict,
        ]);
        $this->lastAiMeta = $result;
        return $result['content'];
    }

    /** @param array<string, mixed> $payload */
    private function stream(string $baseUrl, string $token, array $payload, bool $allowThinkingFallback): string
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
            throw new RuntimeException(
                'Ollama request failed: '.trim((string) data_get($response->json(), 'error', $exception->getMessage())),
                previous: $exception,
            );
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
            throw new RuntimeException(
                'Ollama streaming connection ended unexpectedly: '.$exception->getMessage(),
                previous: $exception,
            );
        }

        if (! $sawChunk) {
            throw new RuntimeException('Ollama returned an empty streaming response.');
        }

        if (trim($content) !== '') {
            return trim($content);
        }

        if ($allowThinkingFallback && trim($thinking) !== '') {
            return trim($thinking);
        }

        if (trim($thinking) !== '') {
            throw new RuntimeException('Ollama returned internal thinking but no final prompt content.');
        }

        throw new RuntimeException('Ollama returned streaming chunks, but no final response content.');
    }

    /** @param array<string, mixed> $payload */
    private function nonStreaming(string $baseUrl, string $token, array $payload): string
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(max(20, (int) config('ai-assets.providers.ollama.connect_timeout_seconds', 15)))
            ->timeout(max(600, (int) config('ai-assets.providers.ollama.timeout_seconds', 300)))
            ->post($baseUrl.'/api/chat', $payload);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException(
                'Ollama request failed: '.trim((string) data_get($response->json(), 'error', $exception->getMessage())),
                previous: $exception,
            );
        }

        $content = data_get($response->json(), 'message.content');
        if (is_string($content) && trim($content) !== '') {
            return trim($content);
        }

        $legacyContent = $response->json('response');
        if (is_string($legacyContent) && trim($legacyContent) !== '') {
            return trim($legacyContent);
        }

        throw new RuntimeException('Ollama returned internal thinking but no final prompt content.');
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

    private function retryable(RuntimeException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'curl error 56')
            || str_contains($message, 'unexpected eof')
            || str_contains($message, 'connection reset')
            || str_contains($message, 'connection refused')
            || str_contains($message, 'internal thinking but no final prompt content')
            || str_contains($message, 'streaming connection ended unexpectedly')
            || str_contains($message, 'empty streaming response');
    }

    private function numPredictForDepth(string $depth): int
    {
        return match ($depth) {
            'compact' => 700,
            'standard' => 1100,
            'detailed' => 1600,
            default => 2200,
        };
    }

    private function lengthRule(string $depth): string
    {
        return match ($depth) {
            'compact' => 'Aim for approximately 80-140 words while remaining concrete.',
            'standard' => 'Aim for approximately 140-220 words with clear subject and environment detail.',
            'detailed' => 'Aim for approximately 220-350 words with strong person-by-person and environment detail.',
            default => 'Aim for approximately 300-500 words with rich person-by-person, setting, object, atmosphere, and emotional detail.',
        };
    }

    /** @param array<int, string> $issues */
    private function implodeIssues(array $issues): string
    {
        return implode("\n- ", $issues);
    }

    /** @param array<int, string> $needles */
    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function yesNo(bool $value): string
    {
        return $value ? 'yes' : 'no';
    }
}
