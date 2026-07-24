<?php

namespace App\Services\Ai\Providers;

use App\Contracts\AssetAiProvider;
use App\Services\Ai\Support\AssetMetadataSchema;
use App\Services\Ai\Support\JsonResponseDecoder;
use Illuminate\Http\Client\ConnectionException;
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

        $payload = [
            'model' => $this->model(),
            'messages' => [[
                'role' => 'user',
                'content' => AssetMetadataSchema::prompt($context),
                'images' => [base64_encode($contents)],
            ]],
            'stream' => true,
            'think' => (bool) config('ai-assets.providers.ollama.think', false),
            'format' => AssetMetadataSchema::schema(),
            'keep_alive' => (string) config('ai-assets.providers.ollama.keep_alive', '10m'),
            'options' => [
                'temperature' => (float) config('ai-assets.providers.ollama.temperature', 0.1),
                'num_predict' => (int) config('ai-assets.providers.ollama.num_predict', 2048),
            ],
        ];

        $attempts = max(1, (int) config('ai-assets.providers.ollama.retry_times', 1) + 1);
        $sleepMilliseconds = max(0, (int) config('ai-assets.providers.ollama.retry_sleep_milliseconds', 750));
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return $this->sendStreamingRequest($payload);
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
            'Ollama request failed after retrying: '.($lastException?->getMessage() ?? 'unknown transport error'),
            previous: $lastException,
        );
    }

    /** @param array<string, mixed> $payload */
    private function sendStreamingRequest(array $payload): array
    {
        $baseUrl = rtrim((string) config('ai-assets.providers.ollama.base_url'), '/');
        $token = (string) config('ai-assets.providers.ollama.token');

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
        $model = $this->model();
        $doneReason = '';
        $inputTokens = null;
        $outputTokens = null;
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
                        $this->consumeChunk(
                            $line,
                            $content,
                            $thinking,
                            $model,
                            $doneReason,
                            $inputTokens,
                            $outputTokens,
                        );
                    }
                }
            }

            $finalLine = trim($buffer);
            if ($finalLine !== '') {
                $sawChunk = true;
                $this->consumeChunk(
                    $finalLine,
                    $content,
                    $thinking,
                    $model,
                    $doneReason,
                    $inputTokens,
                    $outputTokens,
                );
            }
        } catch (Throwable $exception) {
            throw new RuntimeException('Ollama streaming connection ended unexpectedly: '.$exception->getMessage(), previous: $exception);
        }

        if (! $sawChunk) {
            throw new RuntimeException('Ollama returned an empty streaming response.');
        }

        $suggestions = null;
        $decodeErrors = [];

        foreach (['message.content' => $content, 'message.thinking' => $thinking] as $source => $candidate) {
            if (trim($candidate) === '') {
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
            $details = [];
            if ($doneReason !== '') {
                $details[] = 'done_reason='.$doneReason;
            }
            if (trim($content) !== '') {
                $details[] = 'content_present';
            }
            if (trim($thinking) !== '') {
                $details[] = 'thinking_output_present';
            }
            if ($decodeErrors !== []) {
                $details[] = 'metadata_json_invalid';
            }

            throw new RuntimeException(
                'Ollama did not return usable metadata'.($details !== [] ? ' ('.implode(', ', $details).').' : '.')
            );
        }

        return [
            'provider' => $this->key(),
            'model' => $model,
            'suggestions' => $suggestions,
            'usage' => [
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'total_tokens' => $inputTokens !== null && $outputTokens !== null ? $inputTokens + $outputTokens : null,
            ],
        ];
    }

    private function consumeChunk(
        string $line,
        string &$content,
        string &$thinking,
        string &$model,
        string &$doneReason,
        ?int &$inputTokens,
        ?int &$outputTokens,
    ): void {
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

        if (is_string($json['model'] ?? null) && trim($json['model']) !== '') {
            $model = $json['model'];
        }

        if (is_string($json['done_reason'] ?? null)) {
            $doneReason = trim($json['done_reason']);
        }

        if (is_numeric($json['prompt_eval_count'] ?? null)) {
            $inputTokens = (int) $json['prompt_eval_count'];
        }

        if (is_numeric($json['eval_count'] ?? null)) {
            $outputTokens = (int) $json['eval_count'];
        }

        if (isset($json['error']) && is_string($json['error']) && trim($json['error']) !== '') {
            throw new RuntimeException('Ollama request failed: '.trim($json['error']));
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
}
