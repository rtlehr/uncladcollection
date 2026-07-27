<?php

namespace App\Services\SearchIntelligence;

use App\Models\SearchTerm;
use App\Models\SearchTermMapping;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SearchTermAiService
{
    public function analyze(SearchTerm $term): SearchTermMapping
    {
        $base = rtrim((string) config('search-intelligence.ollama.base_url'), '/');
        $token = (string) config('search-intelligence.ollama.token');
        $model = (string) config('search-intelligence.ollama.model');
        if ($base === '' || $token === '' || $model === '') throw new RuntimeException('Search intelligence AI is not configured.');

        $prompt = "Analyze this marketplace search phrase for spelling and intent. Preserve names, places, brands, events, and niche naturist terminology unless correction is clearly justified. Return JSON only with keys canonical_term (string), synonyms (array of up to 6 strings), intent_category (string), confidence (number 0 to 1), explanation (short string). Search phrase: {$term->display_term}";
        $response = Http::withToken($token)->acceptJson()->asJson()
            ->connectTimeout(15)->timeout((int) config('search-intelligence.ollama.timeout_seconds', 180))
            ->post($base.'/api/chat', [
                'model' => $model,
                'messages' => [['role'=>'user','content'=>$prompt]],
                'stream' => false,
                'think' => false,
                'format' => 'json',
                'options' => ['temperature'=>0.1,'num_predict'=>500],
            ])->throw()->json();

        $content = (string) data_get($response, 'message.content', data_get($response, 'response', ''));
        $data = json_decode($content, true);
        if (! is_array($data)) throw new RuntimeException('Qwen returned an invalid search intelligence response.');

        return SearchTermMapping::query()->updateOrCreate(['search_term_id'=>$term->id], [
            'suggested_canonical_term' => mb_substr(trim((string) ($data['canonical_term'] ?? $term->display_term)), 0, 120),
            'suggested_synonyms' => collect($data['synonyms'] ?? [])->map(fn ($v) => mb_substr(trim((string) $v),0,120))->filter()->unique()->take(6)->values()->all(),
            'intent_category' => mb_substr(trim((string) ($data['intent_category'] ?? 'general')),0,80),
            'confidence' => max(0, min(1, (float) ($data['confidence'] ?? 0))),
            'ai_explanation' => trim((string) ($data['explanation'] ?? '')),
            'status' => SearchTermMapping::STATUS_PENDING,
            'source' => 'ai', 'provider' => 'ollama', 'model' => $model, 'analyzed_at' => now(),
        ]);
    }
}
