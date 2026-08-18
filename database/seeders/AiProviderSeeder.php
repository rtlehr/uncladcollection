<?php

namespace Database\Seeders;

use App\Models\AiFeatureAssignment;
use App\Models\AiProvider;
use App\Services\Ai\AiFeatureCatalog;
use Illuminate\Database\Seeder;

class AiProviderSeeder extends Seeder
{
    public function run(): void
    {
        $ollama = AiProvider::query()->firstOrCreate(['slug' => 'ollama'], [
            'name' => 'Current Ollama / Qwen',
            'driver' => 'ollama',
            'base_url' => (string) config(
                'ai-assets.providers.ollama.base_url',
                'https://ai.uncladcollection.com'
            ),
            'api_key' => (string) config('ai-assets.providers.ollama.token'),
            'default_model' => (string) config(
                'ai-assets.providers.ollama.model',
                'qwen3-vl:8b'
            ),
            'connect_timeout_seconds' => (int) config(
                'ai-assets.providers.ollama.connect_timeout_seconds',
                15
            ),
            'timeout_seconds' => (int) config(
                'ai-assets.providers.ollama.timeout_seconds',
                300
            ),
            'retry_times' => 3,
            'is_enabled' => true,
        ]);

        $venice = AiProvider::query()->firstOrCreate(['slug' => 'venice'], [
            'name' => 'Venice AI',
            'driver' => 'venice',
            'base_url' => (string) config(
                'ai-assets.providers.venice.base_url',
                'https://api.venice.ai/api/v1'
            ),
            'api_key' => (string) config('ai-assets.providers.venice.token'),
            'default_model' => (string) config(
                'ai-assets.providers.venice.model',
                'gemini-2.5-flash'
            ),
            'connect_timeout_seconds' => (int) config(
                'ai-assets.providers.venice.connect_timeout_seconds',
                20
            ),
            'timeout_seconds' => (int) config(
                'ai-assets.providers.venice.timeout_seconds',
                300
            ),
            'retry_times' => 2,
            'is_enabled' => true,
        ]);

        AiProvider::query()->firstOrCreate(['slug' => 'openai'], [
            'name' => 'OpenAI',
            'driver' => 'openai',
            'base_url' => 'https://api.openai.com/v1',
            'default_model' => 'gpt-4.1-mini',
            'connect_timeout_seconds' => 20,
            'timeout_seconds' => 300,
            'retry_times' => 2,
            'is_enabled' => false,
        ]);

        foreach (array_keys(AiFeatureCatalog::FEATURES) as $feature) {
            AiFeatureAssignment::query()->firstOrCreate(
                ['feature' => $feature],
                [
                    'primary_provider_id' => $ollama->id,
                    'primary_model' => $ollama->default_model,
                    'fallback_enabled' => false,
                ]
            );
        }
    }
}