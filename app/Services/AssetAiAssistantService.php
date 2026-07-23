<?php

namespace App\Services;

use App\Contracts\AssetAiProvider;
use App\Services\Ai\Providers\OllamaAssetAiProvider;
use App\Services\Ai\Providers\OpenAiAssetAiProvider;
use RuntimeException;
use Throwable;

class AssetAiAssistantService
{
    /** @var array<string, AssetAiProvider> */
    private array $providers;

    public function __construct(
        OllamaAssetAiProvider $ollama,
        OpenAiAssetAiProvider $openAi,
    ) {
        $this->providers = [
            $ollama->key() => $ollama,
            $openAi->key() => $openAi,
        ];
    }

    public function defaultProvider(): string
    {
        return (string) config('ai-assets.default_provider', 'ollama');
    }

    /** @return array<int, array{value: string, label: string, model: string}> */
    public function availableProviders(): array
    {
        $configured = [];

        foreach ($this->providers as $provider) {
            if ($provider->isConfigured()) {
                $configured[] = [
                    'value' => $provider->key(),
                    'label' => $provider->label(),
                    'model' => $provider->model(),
                ];
            }
        }

        return $configured;
    }

    public function modelFor(?string $provider = null): ?string
    {
        $key = $provider ?: $this->defaultProvider();

        return $this->providers[$key]->model() ?? null;
    }

    public function isEnabled(): bool
    {
        return (bool) config('ai-assets.enabled', true) && $this->availableProviders() !== [];
    }

    public function analyze(string $imagePath, array $context = [], ?string $provider = null): array
    {
        $requested = $provider ?: $this->defaultProvider();
        $primary = $this->provider($requested);

        if (! $primary->isConfigured()) {
            throw new RuntimeException("The selected AI provider [{$requested}] is not configured.");
        }

        try {
            return $primary->analyze($imagePath, $context) + [
                'requested_provider' => $requested,
                'fallback_used' => false,
            ];
        } catch (Throwable $primaryException) {
            $fallback = $this->fallbackProviderFor($requested);

            if ($fallback === null) {
                throw $primaryException;
            }

            try {
                return $fallback->analyze($imagePath, $context) + [
                    'requested_provider' => $requested,
                    'fallback_used' => true,
                    'primary_error' => $primaryException->getMessage(),
                ];
            } catch (Throwable $fallbackException) {
                throw new RuntimeException(
                    "AI analysis failed with {$primary->label()} and fallback {$fallback->label()}. "
                    .'Primary error: '.$primaryException->getMessage().' Fallback error: '.$fallbackException->getMessage(),
                    previous: $fallbackException,
                );
            }
        }
    }

    private function provider(string $key): AssetAiProvider
    {
        if (! isset($this->providers[$key])) {
            throw new RuntimeException("Unknown AI provider [{$key}].");
        }

        return $this->providers[$key];
    }

    private function fallbackProviderFor(string $primary): ?AssetAiProvider
    {
        if (! (bool) config('ai-assets.fallback_enabled', true)) {
            return null;
        }

        $fallbackKey = trim((string) config('ai-assets.fallback_provider', 'openai'));
        if ($fallbackKey === '' || $fallbackKey === $primary || ! isset($this->providers[$fallbackKey])) {
            return null;
        }

        $fallback = $this->providers[$fallbackKey];

        return $fallback->isConfigured() ? $fallback : null;
    }
}
