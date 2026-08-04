<?php

namespace App\Services\Ai;

use App\Models\AiFeatureAssignment;
use App\Models\AiProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class AiTextGateway
{
    /**
     * @param array<string,mixed> $options
     * @return array{content:string,provider:string,model:string,usage:array<string,int|null>}
     */
    public function generate(string $feature, string $prompt, array $options = []): array
    {
        $assignment = AiFeatureAssignment::query()->with(['primaryProvider', 'fallbackProvider'])->where('feature', $feature)->first();

        if (! $assignment || ! $assignment->primaryProvider) {
            return $this->legacyOllama($prompt, $options);
        }

        try {
            return $this->send($assignment->primaryProvider, $assignment->primary_model, $prompt, $options);
        } catch (Throwable $primaryException) {
            if (! $assignment->fallback_enabled || ! $assignment->fallbackProvider) {
                throw $primaryException;
            }

            try {
                return $this->send($assignment->fallbackProvider, $assignment->fallback_model, $prompt, $options);
            } catch (Throwable $fallbackException) {
                throw new RuntimeException(
                    'Primary AI provider failed: '.$primaryException->getMessage().' Fallback provider also failed: '.$fallbackException->getMessage(),
                    previous: $fallbackException,
                );
            }
        }
    }

    /** @return array<int,array{id:string,name:string}> */
    public function listModels(AiProvider $provider): array
    {
        $provider->refresh();
        $base = rtrim($provider->base_url, '/');
        $request = Http::acceptJson()->connectTimeout($provider->connect_timeout_seconds)->timeout(min(60, $provider->timeout_seconds));
        if (filled($provider->api_key)) $request = $request->withToken($provider->api_key);

        try {
            if ($provider->driver === 'ollama') {
                $json = $request->get($base.'/api/tags')->throw()->json();
                return collect($json['models'] ?? [])->map(fn ($m) => ['id' => (string) ($m['name'] ?? ''), 'name' => (string) ($m['name'] ?? '')])->filter(fn ($m) => $m['id'] !== '')->values()->all();
            }

            $json = $request->get($base.'/models')->throw()->json();
            return collect($json['data'] ?? [])->map(fn ($m) => ['id' => (string) ($m['id'] ?? ''), 'name' => (string) ($m['id'] ?? '')])->filter(fn ($m) => $m['id'] !== '')->values()->all();
        } catch (Throwable $e) {
            throw new RuntimeException('Could not load models: '.$e->getMessage(), previous: $e);
        }
    }

    /** @return array{success:bool,message:string,model:string|null,duration_ms:int} */
    public function test(AiProvider $provider): array
    {
        $started = microtime(true);
        try {
            $result = $this->send($provider, $provider->default_model, 'Reply with the word OK only.', ['max_tokens' => 512, 'temperature' => 0.0]);
            return ['success' => true, 'message' => trim($result['content']) ?: 'Connected', 'model' => $result['model'], 'duration_ms' => (int) round((microtime(true) - $started) * 1000)];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'model' => $provider->default_model, 'duration_ms' => (int) round((microtime(true) - $started) * 1000)];
        }
    }

    /** @param array<string,mixed> $options */
    private function send(AiProvider $provider, ?string $modelOverride, string $prompt, array $options): array
    {
        if (! $provider->is_enabled) throw new RuntimeException("AI provider [{$provider->name}] is disabled.");
        $model = trim((string) ($modelOverride ?: $provider->default_model));
        if ($model === '') throw new RuntimeException("AI provider [{$provider->name}] has no model configured.");

        return $provider->driver === 'ollama'
            ? $this->sendOllama($provider, $model, $prompt, $options)
            : $this->sendOpenAiCompatible($provider, $model, $prompt, $options);
    }

    private function sendOllama(AiProvider $provider, string $model, string $prompt, array $options): array
    {
        $payload = [
            'model' => $model,
            'stream' => false,
            'think' => false,
            'messages' => [['role' => 'user', 'content' => "/no_think\n".$prompt]],
            'options' => [
                'temperature' => (float) ($options['temperature'] ?? 0.2),
                'num_predict' => (int) ($options['max_tokens'] ?? 3000),
            ],
        ];
        if (($options['json'] ?? false) === true) $payload['format'] = 'json';

        $response = $this->request($provider, fn ($http) => $http->post(rtrim($provider->base_url, '/').'/api/chat', $payload));
        $json = $response->json();
        $content = trim((string) data_get($json, 'message.content', ''));
        if ($content === '') $content = trim((string) data_get($json, 'response', ''));
        if ($content === '' && ($options['json'] ?? false) === true) {
            $content = $this->validJsonFromReasoning($json);
        }

        // Qwen can spend a very small output allowance entirely on hidden
        // reasoning, leaving message.content empty even when think=false.
        // Retry once with a larger allowance and an explicit /no_think command.
        if ($content === '') {
            $retryPayload = $payload;
            $retryPayload['messages'] = [[
                'role' => 'user',
                'content' => "/no_think\n".$prompt,
            ]];
            $retryPayload['options']['num_predict'] = max(512, (int) ($payload['options']['num_predict'] ?? 0));

            $response = $this->request(
                $provider,
                fn ($http) => $http->post(rtrim($provider->base_url, '/').'/api/chat', $retryPayload),
            );
            $json = $response->json();
            $content = trim((string) data_get($json, 'message.content', ''));
            if ($content === '') $content = trim((string) data_get($json, 'response', ''));
            if ($content === '' && ($options['json'] ?? false) === true) {
                $content = $this->validJsonFromReasoning($json);
            }
        }

        if ($content === '') throw new RuntimeException("AI provider [{$provider->name}] returned an empty response after suppressing model reasoning.");

        return [
            'content' => $content,
            'provider' => $provider->slug,
            'model' => (string) ($json['model'] ?? $model),
            'usage' => [
                'input_tokens' => isset($json['prompt_eval_count']) ? (int) $json['prompt_eval_count'] : null,
                'output_tokens' => isset($json['eval_count']) ? (int) $json['eval_count'] : null,
                'total_tokens' => isset($json['prompt_eval_count'], $json['eval_count']) ? (int) $json['prompt_eval_count'] + (int) $json['eval_count'] : null,
            ],
        ];
    }


    /**
     * Recover structured JSON only when Qwen places the requested JSON object
     * in its reasoning field. Raw reasoning is never returned to the caller.
     *
     * @param array<string,mixed> $response
     */
    private function validJsonFromReasoning(array $response): string
    {
        $reasoning = trim((string) data_get($response, 'message.thinking', ''));
        if ($reasoning === '') {
            $reasoning = trim((string) ($response['thinking'] ?? ''));
        }

        if ($reasoning === '') {
            return '';
        }

        $reasoning = preg_replace('/^```(?:json)?\s*/i', '', $reasoning) ?? $reasoning;
        $reasoning = preg_replace('/\s*```$/', '', $reasoning) ?? $reasoning;
        $reasoning = preg_replace('/<think>.*?<\/think>/is', '', $reasoning) ?? $reasoning;
        $reasoning = trim($reasoning);

        if ($this->isValidJsonObject($reasoning)) {
            return $reasoning;
        }

        $start = strpos($reasoning, '{');
        $end = strrpos($reasoning, '}');

        if ($start === false || $end === false || $end <= $start) {
            return '';
        }

        $candidate = trim(substr($reasoning, $start, $end - $start + 1));

        return $this->isValidJsonObject($candidate) ? $candidate : '';
    }

    private function isValidJsonObject(string $candidate): bool
    {
        if ($candidate === '' || ! str_starts_with($candidate, '{')) {
            return false;
        }

        $decoded = json_decode($candidate, true);

        return is_array($decoded) && json_last_error() === JSON_ERROR_NONE;
    }

    private function sendOpenAiCompatible(AiProvider $provider, string $model, string $prompt, array $options): array
    {
        $payload = [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => (float) ($options['temperature'] ?? 0.2),
            'max_tokens' => (int) ($options['max_tokens'] ?? 3000),
        ];
        if (($options['json'] ?? false) === true) $payload['response_format'] = ['type' => 'json_object'];

        $response = $this->request($provider, fn ($http) => $http->post(rtrim($provider->base_url, '/').'/chat/completions', $payload));
        $json = $response->json();
        $content = trim((string) data_get($json, 'choices.0.message.content', ''));
        if ($content === '') throw new RuntimeException("AI provider [{$provider->name}] returned an empty response.");
        $usage = is_array($json['usage'] ?? null) ? $json['usage'] : [];

        return [
            'content' => $content,
            'provider' => $provider->slug,
            'model' => (string) ($json['model'] ?? $model),
            'usage' => [
                'input_tokens' => isset($usage['prompt_tokens']) ? (int) $usage['prompt_tokens'] : null,
                'output_tokens' => isset($usage['completion_tokens']) ? (int) $usage['completion_tokens'] : null,
                'total_tokens' => isset($usage['total_tokens']) ? (int) $usage['total_tokens'] : null,
            ],
        ];
    }

    private function request(AiProvider $provider, callable $callback)
    {
        $attempts = max(1, $provider->retry_times + 1);
        $last = null;
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $http = Http::acceptJson()->asJson()->connectTimeout($provider->connect_timeout_seconds)->timeout($provider->timeout_seconds);
                if (filled($provider->api_key)) $http = $http->withToken($provider->api_key);
                return $callback($http)->throw();
            } catch (ConnectionException|RequestException $e) {
                $last = $e;
                if ($attempt < $attempts) usleep(min(10000, 1000 * (2 ** ($attempt - 1))) * 1000);
            }
        }
        throw new RuntimeException("AI provider [{$provider->name}] request failed after retrying: ".($last?->getMessage() ?? 'unknown error'), previous: $last);
    }

    private function legacyOllama(string $prompt, array $options): array
    {
        $provider = new AiProvider([
            'name' => 'Legacy Ollama', 'slug' => 'ollama', 'driver' => 'ollama',
            'base_url' => (string) config('ai-assets.providers.ollama.base_url'),
            'api_key' => (string) config('ai-assets.providers.ollama.token'),
            'default_model' => (string) config('ai-assets.providers.ollama.model'),
            'connect_timeout_seconds' => (int) config('ai-assets.providers.ollama.connect_timeout_seconds', 15),
            'timeout_seconds' => (int) config('ai-assets.providers.ollama.timeout_seconds', 300),
            'retry_times' => max(2, (int) config('ai-assets.providers.ollama.retry_times', 1)),
            'is_enabled' => true,
        ]);
        return $this->sendOllama($provider, $provider->default_model, $prompt, $options);
    }
}
