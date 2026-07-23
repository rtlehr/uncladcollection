<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAssetSuggestion;
use App\Models\Asset;
use App\Services\AssetAiSourceService;
use App\Services\AssetColorAnalysisService;
use App\Services\OpenAiAssetAssistantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;

class AssetAiSuggestionController extends Controller
{
    public function store(Request $request, Asset $asset, AssetAiSourceService $sourceService, AssetColorAnalysisService $colors, OpenAiAssetAssistantService $assistant): RedirectResponse
    {
        $request->validate([
            'adult_content_confirmed' => ['accepted'],
            'non_sexual_content_confirmed' => ['accepted'],
        ]);

        $record = $asset->aiSuggestions()->create([
            'requested_by' => $request->user()?->id,
            'provider' => 'openai',
            'model' => config('ai-assets.model'),
            'status' => 'processing',
        ]);

        try {
            $source = $sourceService->resolve($asset);
            $local = $colors->analyze($source['path']);
            $result = $assistant->analyze($source['path'], ['title' => $asset->title]);

            $record->update([
                'status' => 'completed',
                'source_type' => $source['type'],
                'source_reference' => $source['reference'],
                'suggestions' => $result['suggestions'],
                'local_analysis' => $local,
                'input_tokens' => $result['usage']['input_tokens'],
                'output_tokens' => $result['usage']['output_tokens'],
                'total_tokens' => $result['usage']['total_tokens'],
                'completed_at' => now(),
            ]);

            return back()->with('success', 'AI suggestions are ready for review.');
        } catch (Throwable $exception) {
            report($exception);
            $record->update(['status' => 'failed', 'error_message' => $exception->getMessage(), 'completed_at' => now()]);
            return back()->withErrors(['ai_assistant' => $exception->getMessage()]);
        }
    }

    public function apply(Request $request, Asset $asset, AiAssetSuggestion $suggestion): RedirectResponse
    {
        abort_unless($suggestion->asset_id === $asset->id && $suggestion->status === 'completed', 404);

        $validated = $request->validate([
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['string', 'in:title,description,alt_text,seo_title,seo_description,keywords,dominant_colors,detected_objects'],
            'keyword_mode' => ['nullable', 'in:replace,append'],
        ]);

        $data = [];
        $suggestions = $suggestion->suggestions ?? [];
        $local = $suggestion->local_analysis ?? [];

        foreach ($validated['fields'] as $field) {
            $value = match ($field) {
                'dominant_colors' => $local['dominant_colors'] ?? [],
                'detected_objects' => $suggestions['objects'] ?? [],
                default => $suggestions[$field] ?? null,
            };

            if ($field === 'keywords' && ($validated['keyword_mode'] ?? 'replace') === 'append') {
                $value = array_values(array_unique(array_merge($asset->keywords ?? [], Arr::wrap($value))));
            }

            if ($value !== null) {
                $data[$field] = $value;
            }
        }

        $asset->update($data);
        $suggestion->update(['reviewed_at' => now()]);

        return back()->with('success', 'Selected AI suggestions were applied.');
    }
}
