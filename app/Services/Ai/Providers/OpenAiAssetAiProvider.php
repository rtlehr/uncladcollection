<?php

namespace App\Services\Ai\Providers;

use App\Contracts\AssetAiProvider;
use App\Services\Ai\Support\AssetMetadataSchema;
use App\Services\Ai\Support\JsonResponseDecoder;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiAssetAiProvider implements AssetAiProvider
{
    public function key(): string
    {
        return 'openai';
    }

    public function label(): string
    {
        return 'OpenAI';
    }

    public function model(): string
    {
        return (string) config('ai-assets.providers.openai.model', 'gpt-4.1-mini');
    }

    public function isConfigured(): bool
    {
        return trim((string) config('ai-assets.providers.openai.api_key')) !== '' && $this->model() !== '';
    }

    public function analyze(string $imagePath, array $context = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('The OpenAI provider is not fully configured.');
        }

        $contents = file_get_contents($imagePath);
        if ($contents === false) {
            throw new RuntimeException('The asset preview could not be read for AI analysis.');
        }

        $mime = mime_content_type($imagePath) ?: 'image/jpeg';
        $dataUrl = 'data:'.$mime.';base64,'.base64_encode($contents);
        $baseUrl = rtrim((string) config('ai-assets.providers.openai.base_url'), '/');

        $response = Http::withToken((string) config('ai-assets.providers.openai.api_key'))
            ->acceptJson()
            ->connectTimeout((int) config('ai-assets.providers.openai.connect_timeout_seconds', 15))
            ->timeout((int) config('ai-assets.providers.openai.timeout_seconds', 120))
            ->retry(
                (int) config('ai-assets.providers.openai.retry_times', 1),
                (int) config('ai-assets.providers.openai.retry_sleep_milliseconds', 750),
                throw: false,
            )
            ->post($baseUrl.'/responses', [
                'model' => $this->model(),
                'input' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'input_text', 'text' => AssetMetadataSchema::prompt($context)],
                        ['type' => 'input_image', 'image_url' => $dataUrl, 'detail' => 'low'],
                    ],
                ]],
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'asset_metadata',
                        'strict' => true,
                        'schema' => AssetMetadataSchema::schema(),
                    ],
                ],
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            $message = data_get($response->json(), 'error.message', $exception->getMessage());
            throw new RuntimeException('OpenAI request failed: '.trim((string) $message), previous: $exception);
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException('OpenAI returned an unreadable response.');
        }

        if (($json['status'] ?? null) === 'incomplete') {
            throw new RuntimeException('OpenAI analysis was incomplete: '.data_get($json, 'incomplete_details.reason', 'unknown reason'));
        }

        $text = null;
        $refusal = null;

        foreach (Arr::wrap($json['output'] ?? []) as $outputItem) {
            foreach (Arr::wrap($outputItem['content'] ?? []) as $contentItem) {
                if (($contentItem['type'] ?? null) === 'refusal') {
                    $refusal = $contentItem['refusal'] ?? $contentItem['text'] ?? 'The provider declined the request.';
                }

                if (($contentItem['type'] ?? null) === 'output_text' && is_string($contentItem['text'] ?? null)) {
                    $text = $contentItem['text'];
                    break 2;
                }
            }
        }

        if ($refusal !== null) {
            throw new RuntimeException('OpenAI declined to analyze this asset: '.trim((string) $refusal));
        }

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('OpenAI did not return metadata text.');
        }

        return [
            'provider' => $this->key(),
            'model' => $this->model(),
            'suggestions' => JsonResponseDecoder::decode($text),
            'usage' => [
                'input_tokens' => data_get($json, 'usage.input_tokens'),
                'output_tokens' => data_get($json, 'usage.output_tokens'),
                'total_tokens' => data_get($json, 'usage.total_tokens'),
            ],
        ];
    }
}
