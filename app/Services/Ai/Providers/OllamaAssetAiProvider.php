<?php

namespace App\Services\Ai\Providers;

use App\Contracts\AssetAiProvider;
use App\Services\Ai\Support\AssetMetadataSchema;
use App\Services\Ai\Support\JsonResponseDecoder;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class OllamaAssetAiProvider implements AssetAiProvider
{
    public function key(): string
    {
        return 'ollama';
    }

    public function label(): string
    {
        return 'Qwen3-VL (Ollama)';
    }

    public function model(): string
    {
        return (string) config('ai-assets.providers.ollama.model', 'qwen3-vl:8b');
    }

    public function isConfigured(): bool
    {
        return trim((string) config('ai-assets.providers.ollama.base_url')) !== ''
            && trim((string) config('ai-assets.providers.ollama.token')) !== ''
            && $this->model() !== '';
    }

    public function analyze(string $imagePath, array $context = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('The Ollama AI provider is not fully configured.');
        }

        $contents = file_get_contents($imagePath);
        if ($contents === false) {
            throw new RuntimeException('The asset preview could not be read for AI analysis.');
        }

        $baseUrl = rtrim((string) config('ai-assets.providers.ollama.base_url'), '/');
        $token = (string) config('ai-assets.providers.ollama.token');

        $response = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->connectTimeout((int) config('ai-assets.providers.ollama.connect_timeout_seconds', 15))
            ->timeout((int) config('ai-assets.providers.ollama.timeout_seconds', 300))
            ->retry(
                (int) config('ai-assets.providers.ollama.retry_times', 1),
                (int) config('ai-assets.providers.ollama.retry_sleep_milliseconds', 750),
                throw: false,
            )
            ->post($baseUrl.'/api/chat', [
                'model' => $this->model(),
                'messages' => [[
                    'role' => 'user',
                    'content' => AssetMetadataSchema::prompt($context),
                    'images' => [base64_encode($contents)],
                ]],
                'stream' => false,
                'think' => (bool) config('ai-assets.providers.ollama.think', false),
                'format' => AssetMetadataSchema::schema(),
                'keep_alive' => (string) config('ai-assets.providers.ollama.keep_alive', '10m'),
                'options' => [
                    'temperature' => (float) config('ai-assets.providers.ollama.temperature', 0.1),
                    'num_predict' => (int) config('ai-assets.providers.ollama.num_predict', 2048),
                ],
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            $message = data_get($response->json(), 'error', $exception->getMessage());
            throw new RuntimeException('Ollama request failed: '.trim((string) $message), previous: $exception);
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('Ollama returned an unreadable response.');
        }

        $candidates = [
            'message.content' => data_get($json, 'message.content'),
            'response' => $json['response'] ?? null,
            // Some Qwen3-VL/Ollama builds put the structured answer in the thinking field
            // even when think=false. We only accept it if it decodes and validates as metadata.
            'message.thinking' => data_get($json, 'message.thinking'),
            'thinking' => $json['thinking'] ?? null,
        ];

        $suggestions = null;
        $decodeErrors = [];

        foreach ($candidates as $source => $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            try {
                $suggestions = JsonResponseDecoder::decode($candidate);
                break;
            } catch (Throwable $exception) {
                $decodeErrors[$source] = $exception->getMessage();
            }
        }

        if (! is_array($suggestions)) {
            $doneReason = is_string($json['done_reason'] ?? null) ? trim($json['done_reason']) : '';
            $availableSources = array_keys(array_filter(
                $candidates,
                static fn (mixed $value): bool => is_string($value) && trim($value) !== '',
            ));

            $details = [];
            if ($doneReason !== '') {
                $details[] = 'done_reason='.$doneReason;
            }
            if ($availableSources !== []) {
                $details[] = 'text_fields='.implode('|', $availableSources);
            }
            if ($decodeErrors !== []) {
                $details[] = 'metadata_json_invalid';
            }

            throw new RuntimeException(
                'Ollama did not return usable metadata'.($details !== [] ? ' ('.implode(', ', $details).').' : '.')
            );
        }

        $inputTokens = is_numeric($json['prompt_eval_count'] ?? null) ? (int) $json['prompt_eval_count'] : null;
        $outputTokens = is_numeric($json['eval_count'] ?? null) ? (int) $json['eval_count'] : null;

        return [
            'provider' => $this->key(),
            'model' => (string) ($json['model'] ?? $this->model()),
            'suggestions' => $suggestions,
            'usage' => [
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'total_tokens' => $inputTokens !== null && $outputTokens !== null ? $inputTokens + $outputTokens : null,
            ],
        ];
    }
}
