<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Illuminate\Support\Arr;
use JsonException;

class OpenAiAssetAssistantService
{
    public function analyze(string $imagePath, array $context = []): array
    {
        $key = config('ai-assets.api_key');
        if (! is_string($key) || trim($key) === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $mime = mime_content_type($imagePath) ?: 'image/jpeg';
        $dataUrl = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($imagePath));
        $model = (string) config('ai-assets.model');

        $prompt = <<<'PROMPT'
Analyze this marketplace asset preview for cataloging. The administrator has confirmed that all visible people are consenting adults and that the content is non-sexual.

Return factual, neutral stock-marketplace metadata. Do not identify people. Do not infer ethnicity, religion, health, disability, sexual orientation, political beliefs, or other sensitive traits. Do not describe intimate anatomy in detail.

Create: a concise title, customer-facing description, accessible alt text, SEO title, SEO description, 15-25 useful keywords, general visible objects, scene/setting, composition, and suggested relationship themes for finding related assets.
PROMPT;

        if (! empty($context['title'])) {
            $prompt .= "\nExisting title for context only: ".$context['title'];
        }

        $response = Http::withToken($key)
            ->acceptJson()
            ->timeout((int) config('ai-assets.timeout_seconds', 90))
            ->post(config('ai-assets.base_url').'/responses', [
                'model' => $model,
                'input' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'input_text', 'text' => $prompt],
                        ['type' => 'input_image', 'image_url' => $dataUrl, 'detail' => 'low'],
                    ],
                ]],
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'asset_metadata',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'description' => ['type' => 'string'],
                                'alt_text' => ['type' => 'string'],
                                'seo_title' => ['type' => 'string'],
                                'seo_description' => ['type' => 'string'],
                                'keywords' => ['type' => 'array', 'items' => ['type' => 'string']],
                                'objects' => ['type' => 'array', 'items' => ['type' => 'string']],
                                'scene' => ['type' => 'string'],
                                'composition' => ['type' => 'string'],
                                'relationship_suggestions' => ['type' => 'array', 'items' => ['type' => 'string']],
                            ],
                            'required' => ['title','description','alt_text','seo_title','seo_description','keywords','objects','scene','composition','relationship_suggestions'],
                        ],
                    ],
                ],
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            $message = data_get($response->json(), 'error.message', $exception->getMessage());
            throw new RuntimeException((string) $message, previous: $exception);
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException('The AI service returned an unreadable response.');
        }

        if (($json['status'] ?? null) === 'incomplete') {
            $reason = data_get(
                $json,
                'incomplete_details.reason',
                'The response was incomplete.'
            );

            throw new RuntimeException(
                'The AI analysis could not be completed: '.$reason
            );
        }

        $text = null;
        $refusal = null;
        $contentTypes = [];

        foreach (Arr::wrap($json['output'] ?? []) as $outputItem) {
            foreach (Arr::wrap($outputItem['content'] ?? []) as $contentItem) {
                $type = $contentItem['type'] ?? 'unknown';
                $contentTypes[] = $type;

                if ($type === 'refusal') {
                    $refusal = $contentItem['refusal']
                        ?? $contentItem['text']
                        ?? 'The AI declined to analyze this image.';
                }

                if ($type === 'output_text' && is_string($contentItem['text'] ?? null)) {
                    $text = $contentItem['text'];
                    break 2;
                }
            }
        }

        if ($refusal !== null) {
            throw new RuntimeException(
                'The AI declined to analyze this asset: '.trim((string) $refusal)
            );
        }

        if (! is_string($text) || trim($text) === '') {
            $types = array_values(array_unique($contentTypes));

            throw new RuntimeException(
                'The AI response did not contain metadata text.'
                .($types !== [] ? ' Response content types: '.implode(', ', $types).'.' : '')
            );
        }

        $text = trim($text);

        // Defensive handling in case a model wraps JSON in a Markdown code block.
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/\s*```$/', '', $text) ?? $text;

        try {
            $suggestions = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'The AI returned metadata that could not be decoded as JSON: '
                .$exception->getMessage(),
                previous: $exception,
            );
        }

        if (! is_array($suggestions)) {
            throw new RuntimeException(
                'The AI response contained JSON, but not the expected metadata object.'
            );
        }

        return [
            'model' => $model,
            'suggestions' => $suggestions,
            'usage' => [
                'input_tokens' => data_get($json, 'usage.input_tokens'),
                'output_tokens' => data_get($json, 'usage.output_tokens'),
                'total_tokens' => data_get($json, 'usage.total_tokens'),
            ],
        ];
    }
}
