<?php

namespace App\Services\Ai\ContentStudio;

use App\Models\AiContentPolicy;
use App\Models\AiGeneration;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ImagePromptGenerator
{
    public function __construct(private PromptExampleSelector $selector) {}

    public function generate(array $data, ?int $userId = null): AiGeneration
    {
        $data = $this->resolveOptions($data);

        $examples = $this->selector->select(
            $data['description'],
            $data['content_context'],
            $data['intended_use'],
            $data['body_detail_level'],
        );

        $policies = AiContentPolicy::query()
            ->where('is_enabled', true)
            ->whereIn('key', [
                'general-content',
                'family-naturism',
                'image-prompt-'.$data['output_mode'],
            ])
            ->get();

        $generation = AiGeneration::create([
            'feature' => 'image_prompt',
            'status' => 'processing',
            'input_text' => $data['description'],
            'input_context' => $data,
            'prompt_example_ids' => $examples->pluck('id')->all(),
            'policy_keys' => $policies->pluck('key')->all(),
            'requested_by' => $userId,
            'prompt_template_version' => '3',
        ]);

        try {
            $model = (string) config('ai-assets.providers.ollama.model', 'qwen3-vl:8b');
            $system = $this->systemPrompt($data, $examples->all(), $policies->pluck('instructions')->all());
            $output = $this->sanitizePrompt($this->requestPrompt($system, $model, $data));

            if ($output === '') {
                throw new RuntimeException('The AI returned an empty prompt.');
            }

            $initialIssues = $this->detectIssues($output, $data);
            $attemptedRepair = false;

            if ($initialIssues !== []) {
                $attemptedRepair = true;
                $repairPrompt = $this->repairPrompt($data, $output, $initialIssues, $examples->all(), $policies->pluck('instructions')->all());
                $repairedOutput = $this->sanitizePrompt($this->requestPrompt($repairPrompt, $model, $data));

                if ($repairedOutput !== '') {
                    $output = $repairedOutput;
                }
            }

            $finalIssues = $this->detectIssues($output, $data);

            $generation->update([
                'provider' => 'ollama',
                'model' => $model,
                'status' => 'completed',
                'output_text' => $output,
                'output_data' => [
                    'body_detail_level' => $data['body_detail_level'],
                    'description_depth' => $data['description_depth'],
                    'character_detail_level' => $data['character_detail_level'],
                    'environment_detail_level' => $data['environment_detail_level'],
                    'describe_every_visible_person' => $data['describe_every_visible_person'],
                    'validation' => [
                        'attempted_repair' => $attemptedRepair,
                        'initial_issues' => $initialIssues,
                        'final_issues' => $finalIssues,
                    ],
                ],
                'error_message' => null,
            ]);

            foreach ($examples as $example) {
                $example->increment('usage_count', 1, ['last_used_at' => now()]);
            }
        } catch (Throwable $exception) {
            $generation->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        return $generation->fresh();
    }

    private function resolveOptions(array $data): array
    {
        $data['body_detail_level'] = $this->resolveBodyDetailLevel($data);
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

    private function resolveBodyDetailLevel(array $data): string
    {
        $level = (string) ($data['body_detail_level'] ?? '');

        if ($level !== '') {
            return $level;
        }

        return match ($data['content_context'] ?? 'general') {
            'adult_naturism' => 'detailed_adult_anatomy',
            'family_naturism' => 'natural_detail',
            default => 'contextual',
        };
    }

    private function sanitizePrompt(string $output): string
    {
        $output = preg_replace('/^(here is|image prompt:|prompt:|generated prompt:)\s*/i', '', trim($output)) ?: trim($output);

        return trim($output);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function requestPrompt(string $prompt, string $model, array $data): string
    {
        $baseUrl = rtrim((string) config('ai-assets.providers.ollama.base_url'), '/');
        $token = trim((string) config('ai-assets.providers.ollama.token'));

        if ($baseUrl === '' || $token === '') {
            throw new RuntimeException('Ollama is not configured.');
        }

        $numPredict = match ($data['description_depth']) {
            'compact' => 900,
            'standard' => 1400,
            'detailed' => 2200,
            default => 3000,
        };

        $payload = [
            'model' => $model,
            'stream' => true,
            'think' => false,
            'keep_alive' => (string) config('ai-assets.providers.ollama.keep_alive', '10m'),
            'messages' => [[
                'role' => 'user',
                'content' => $prompt,
            ]],
            'options' => [
                'temperature' => 0.35,
                'num_predict' => $numPredict,
            ],
        ];

        $attempts = max(1, (int) config('ai-assets.providers.ollama.retry_times', 1) + 1);
        $sleepMilliseconds = max(0, (int) config('ai-assets.providers.ollama.retry_sleep_milliseconds', 750));
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return $this->sendStreamingRequest($baseUrl, $token, $payload);
            } catch (ConnectionException $exception) {
                $lastException = $exception;
            } catch (RuntimeException $exception) {
                if (! $this->isRetryableTransportFailure($exception)) {
                    throw $exception;
                }

                $lastException = $exception;
            }

            if ($attempt < $attempts && $sleepMilliseconds > 0) {
                usleep($sleepMilliseconds * 1000);
            }
        }

        throw new RuntimeException(
            'Ollama prompt request failed after retrying: '.($lastException?->getMessage() ?? 'unknown transport error'),
            previous: $lastException,
        );
    }

    /** @param array<string, mixed> $payload */
    private function sendStreamingRequest(string $baseUrl, string $token, array $payload): string
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
            $message = data_get($response->json(), 'error', $exception->getMessage());
            throw new RuntimeException('Ollama request failed: '.trim((string) $message), previous: $exception);
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
                        $this->appendFromChunk($line, $content, $thinking);
                    }
                }
            }

            $finalLine = trim($buffer);
            if ($finalLine !== '') {
                $sawChunk = true;
                $this->appendFromChunk($finalLine, $content, $thinking);
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

        if (trim($thinking) !== '') {
            return trim($thinking);
        }

        throw new RuntimeException('Ollama returned streaming chunks, but neither content nor thinking contained a response.');
    }

    private function appendFromChunk(string $line, string &$content, string &$thinking): void
    {
        $json = json_decode($line, true);

        if (! is_array($json)) {
            throw new RuntimeException('Ollama returned an unreadable streaming chunk.');
        }

        if (isset($json['error']) && is_string($json['error']) && trim($json['error']) !== '') {
            throw new RuntimeException('Ollama request failed: '.trim($json['error']));
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
    }

    private function isRetryableTransportFailure(RuntimeException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'curl error 56')
            || str_contains($message, 'unexpected eof')
            || str_contains($message, 'connection reset')
            || str_contains($message, 'streaming connection ended unexpectedly')
            || str_contains($message, 'empty streaming response');
    }

    private function systemPrompt(array $data, array $examples, array $policies): string
    {
        $modeRule = match ($data['output_mode']) {
            'content_only' => 'Return only scene content: subjects, actions, relationships, setting, objects, and relevant mood. Do not mention camera, lens, lighting, color grading, rendering, quality tags, or visual style.',
            'content_composition' => 'Return scene content plus broad framing, placement, orientation, and title-safe space. Do not include camera, lens, rendering, or quality terminology.',
            default => 'Return a complete production-ready image prompt including content, composition, atmosphere, lighting, and visual treatment.',
        };

        $sampleText = collect($examples)
            ->map(fn ($example) => "- {$example->title}: {$example->content}")
            ->implode("\n");

        return "You are the Unclad Collection image prompt assistant. Create one original prompt only, with no introduction or explanation.\n\n"
            ."MODE: {$modeRule}\n"
            ."INTENDED USE: {$data['intended_use']}\n"
            ."CONTENT CONTEXT: {$data['content_context']}\n"
            ."BODY DETAIL LEVEL: {$data['body_detail_level']}\n"
            ."DESCRIPTION DEPTH: {$data['description_depth']}\n"
            ."CHARACTER DETAIL LEVEL: {$data['character_detail_level']}\n"
            ."ENVIRONMENT DETAIL LEVEL: {$data['environment_detail_level']}\n"
            ."DESCRIBE EVERY VISIBLE PERSON: ".($data['describe_every_visible_person'] ? 'yes' : 'no')."\n"
            ."ORIENTATION: {$data['orientation']}\n"
            ."USER IDEA: {$data['description']}\n"
            .'ADDITIONAL INSTRUCTIONS: '.($data['additional_instructions'] ?? 'None')."\n\n"
            ."PROMPT REQUIREMENTS:\n"
            .$this->requirementsForContext($data)
            ."\n\nPOLICIES:\n".implode("\n", $policies)
            ."\n\nRELEVANT EXAMPLES (use as structural inspiration, never copy, and ignore any example that mixes clothed and nude people if the selected context is naturism):\n{$sampleText}";
    }

    private function requirementsForContext(array $data): string
    {
        $common = [
            'Write a single prompt only.',
            'Make the scene specific, concrete, and visually rich rather than generic.',
            'Describe the setting, objects, and surrounding environment with enough detail that the image model can stage the full scene well.',
            'Avoid generic phrases such as "a family scene" or "people gathered together" without visual specifics.',
        ];

        if ($data['describe_every_visible_person']) {
            $common[] = 'Account for each visible person individually in the final prompt, not just as an unnamed crowd. Make each person visually distinguishable through role, appearance, action, or position.';
        }

        $common[] = $this->descriptionDepthRule($data['description_depth']);
        $common[] = $this->characterDetailRule($data['character_detail_level'], $data['content_context'], $data['body_detail_level']);
        $common[] = $this->environmentDetailRule($data['environment_detail_level']);

        if ($data['content_context'] === 'adult_naturism') {
            $specific = [
                'This is a nonsexual adult naturist / nudist scene.',
                'Explicitly state that every visible adult person is nude, naked, or unclothed.',
                'Do not mix clothed and nude participants unless the user explicitly requests that contrast.',
                'Keep the tone matter-of-fact, respectful, and nonsexual.',
                'When people are sitting, standing, walking, or interacting, describe their pose or activity so the image model understands how each person is placed in the scene.',
            ];

            if ($data['body_detail_level'] === 'detailed_adult_anatomy') {
                $specific[] = 'For visible adults, include neutral physical details such as age range, build, hair color or hairstyle, realistic body shape, body hair, breasts or chest, pubic hair, and when adult men are visible, naturally hanging flaccid penis and testicles when appropriate.';
                $specific[] = 'Neutral anatomy detail is allowed, but never sexual language, fetish framing, arousal, or erotic emphasis.';
            } elseif ($data['body_detail_level'] === 'natural_detail') {
                $specific[] = 'For visible adults, include natural physical details such as age range, build, hair color or hairstyle, realistic body shape, body hair, breasts or chest, and other ordinary nonsexual body characteristics.';
            } else {
                $specific[] = 'State clearly that the adults are nude while keeping body description light and scene-focused.';
            }

            return collect(array_merge($common, $specific))->map(fn ($line) => "- {$line}")->implode("\n");
        }

        if ($data['content_context'] === 'family_naturism') {
            $specific = [
                'This is a wholesome, nonsexual family naturism scene.',
                'Explicitly state that the adult family members are nude participants in the naturist setting.',
                'If younger family members are present, make it clear they are part of the naturist family activity, but do not describe or emphasize their anatomy.',
                'When minors are present, keep private areas naturally obscured by distance, angles, water, towels, furniture, foreground objects, or natural positioning.',
                'Do not mix clothed and nude family members unless the user explicitly requests that contrast.',
                'Focus on family interaction, setting, activity, and wholesome ordinary life.',
            ];

            if ($data['body_detail_level'] === 'detailed_adult_anatomy') {
                $specific[] = 'Detailed adult anatomy applies only to adults. Adult men and women may be described with neutral physical detail, but never extend anatomical detail to minors.';
            } elseif ($data['body_detail_level'] === 'natural_detail') {
                $specific[] = 'Give the adult family members natural body detail such as age range, build, hair color or hairstyle, body hair, breasts or chest, and realistic mature features while keeping minors context-only.';
            }

            return collect(array_merge($common, $specific))->map(fn ($line) => "- {$line}")->implode("\n");
        }

        $specific = [
            'Do not introduce nudity unless the user clearly asked for it.',
            'If a person is clothed, describe what they are wearing with useful visual specificity such as garment type, color, and overall look when relevant.',
            'Focus on the requested subjects, actions, setting, and mood.',
        ];

        return collect(array_merge($common, $specific))->map(fn ($line) => "- {$line}")->implode("\n");
    }

    private function descriptionDepthRule(string $depth): string
    {
        return match ($depth) {
            'compact' => 'Keep the prompt concise but still specific.',
            'standard' => 'Give a solid amount of scene detail, enough for a useful image-generation prompt.',
            'detailed' => 'Create a detailed prompt with clearly described people, actions, surroundings, and scene elements.',
            default => 'Create an expanded, richly detailed prompt. Spend extra attention on person-by-person description, surroundings, objects, and visual context.'
        };
    }

    private function characterDetailRule(string $level, string $context, string $bodyDetailLevel): string
    {
        $base = match ($level) {
            'minimal' => 'Keep person descriptions brief: role, action, and one or two identifying details.',
            'standard' => 'Describe each person with role, action, approximate age range, build, hair, and a few visible features.',
            'detailed' => 'Describe each visible person with role, action, approximate age range, build, body shape, hair color or hairstyle, facial expression, and clear placement in the scene.',
            default => 'Describe each visible person in a very detailed way: role, action, approximate age range, body type, build, hair color or hairstyle, expression, pose, placement, and additional visible features that help the model distinguish them.'
        };

        if ($context === 'general') {
            return $base.' If a person is clothed, include what they are wearing when it helps the image model.';
        }

        if ($bodyDetailLevel === 'contextual') {
            return $base.' State nudity clearly for the appropriate people, but keep body description lighter and more scene-oriented.';
        }

        if ($bodyDetailLevel === 'natural_detail') {
            return $base.' When adults are nude, include realistic natural-body details in a nonsexual way.';
        }

        return $base.' When adults are nude, include strong but neutral anatomy detail for the adults in a nonsexual, matter-of-fact way.';
    }

    private function environmentDetailRule(string $level): string
    {
        return match ($level) {
            'minimal' => 'Keep the environment description concise.',
            'standard' => 'Describe the environment clearly with the main background elements and mood.',
            'detailed' => 'Describe the surroundings with meaningful detail, including room or landscape features, furniture or natural elements, objects, weather or seasonal context, and atmosphere.',
            default => 'Describe the surroundings richly and vividly, including architecture or landscape, surfaces, décor, objects, lighting conditions, weather or season, and useful background details that help the scene feel complete.'
        };
    }

    /**
     * @return array<int, string>
     */
    private function detectIssues(string $output, array $data): array
    {
        $issues = [];
        $text = mb_strtolower($output);

        if ($data['content_context'] !== 'general') {
            if (! $this->containsAny($text, ['nude', 'naked', 'unclothed', 'nudist', 'naturist'])) {
                $issues[] = 'The prompt does not explicitly state that the people are nude.';
            }

            if ($this->containsAny($text, ['shirt', 'pants', 'jeans', 'dress', 'sweater', 'bikini', 'swimsuit', 'fully dressed', 'clothed'])) {
                $issues[] = 'The prompt still includes clothed-language even though naturism was selected.';
            }
        }

        if ($data['content_context'] === 'adult_naturism') {
            if ($data['body_detail_level'] === 'detailed_adult_anatomy'
                && ! $this->containsAny($text, ['pubic hair', 'flaccid penis', 'testicles', 'breasts', 'chest hair', 'body hair', 'natural curves'])) {
                $issues[] = 'The prompt does not contain enough neutral adult body detail for the requested anatomy level.';
            }

            if ($this->containsAny($text, ['child', 'children', 'kid', 'kids', 'teen', 'teenage', 'minor'])) {
                $issues[] = 'Adult naturism was selected, but the prompt appears to mention minors.';
            }
        }

        if ($data['content_context'] === 'family_naturism' && $this->containsAny($text, ['minor pubic hair', 'child pubic hair', 'teen pubic hair', 'flaccid penis of the boy', 'child genital', 'teen genital', 'daughter breasts'])) {
            $issues[] = 'The prompt includes unsafe minor anatomy detail.';
        }

        if (in_array($data['description_depth'], ['detailed', 'expanded'], true) && mb_strlen($output) < 420) {
            $issues[] = 'The prompt is too short for the requested description depth.';
        }

        if (in_array($data['character_detail_level'], ['detailed', 'very_detailed'], true)
            && ! $this->containsAny($text, ['hair', 'hairstyle', 'build', 'body type', 'curvy', 'slim', 'muscular', 'soft build', 'expression', 'posture', 'pose'])) {
            $issues[] = 'The prompt does not appear to include enough person-by-person descriptive detail.';
        }

        if (in_array($data['environment_detail_level'], ['detailed', 'rich'], true)
            && ! $this->containsAny($text, ['background', 'surrounded by', 'wooden', 'furniture', 'windows', 'decor', 'landscape', 'shoreline', 'trees', 'fireplace', 'kitchen', 'room', 'weather', 'season'])) {
            $issues[] = 'The prompt does not appear to include enough environmental detail.';
        }

        return $issues;
    }

    private function repairPrompt(array $data, string $draft, array $issues, array $examples, array $policies): string
    {
        $sampleText = collect($examples)
            ->map(fn ($example) => "- {$example->title}: {$example->content}")
            ->implode("\n");

        return "Rewrite the following image prompt so it fully matches the Unclad Collection requirements. Return only the corrected prompt with no preface.\n\n"
            ."SELECTED CONTEXT: {$data['content_context']}\n"
            ."BODY DETAIL LEVEL: {$data['body_detail_level']}\n"
            ."DESCRIPTION DEPTH: {$data['description_depth']}\n"
            ."CHARACTER DETAIL LEVEL: {$data['character_detail_level']}\n"
            ."ENVIRONMENT DETAIL LEVEL: {$data['environment_detail_level']}\n"
            ."DESCRIBE EVERY VISIBLE PERSON: ".($data['describe_every_visible_person'] ? 'yes' : 'no')."\n"
            ."OUTPUT MODE: {$data['output_mode']}\n"
            ."USER IDEA: {$data['description']}\n"
            .'ADDITIONAL INSTRUCTIONS: '.($data['additional_instructions'] ?? 'None')."\n\n"
            ."PROBLEMS TO FIX:\n- ".implode("\n- ", $issues)
            ."\n\nPROMPT REQUIREMENTS:\n"
            .$this->requirementsForContext($data)
            ."\n\nPOLICIES:\n".implode("\n", $policies)
            ."\n\nRELEVANT EXAMPLES (use for structure only, never copy, and ignore any example that conflicts with the selected context):\n{$sampleText}"
            ."\n\nCURRENT DRAFT TO FIX:\n{$draft}";
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
