<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAssetSuggestion;
use App\Models\Asset;
use App\Services\AssetAiSourceService;
use App\Services\AssetColorAnalysisService;
use App\Services\AssetAiAssistantService;
use App\Services\AssetAiPreviewService;
use App\Services\AssetTagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;

class AssetAiSuggestionController extends Controller
{
    public function store(Request $request, Asset $asset, AssetAiSourceService $sourceService, AssetColorAnalysisService $colors, AssetAiPreviewService $previews, AssetAiAssistantService $assistant): RedirectResponse
    {
        $this->extendExecutionTime();

        $validated = $request->validate([
            'adult_content_confirmed' => ['accepted'],
            'non_sexual_content_confirmed' => ['accepted'],
            'provider' => ['nullable', 'string', 'in:ollama,openai'],
        ]);

        $requestedProvider = $validated['provider'] ?? $assistant->defaultProvider();

        $record = $asset->aiSuggestions()->create([
            'requested_by' => $request->user()?->id,
            'provider' => $requestedProvider,
            'model' => $assistant->modelFor($requestedProvider),
            'status' => 'processing',
        ]);

        $previewPath = null;

        try {
            $source = $sourceService->resolve($asset);
            $local = $colors->analyze($source['path']);
            $previewPath = $previews->create($source['path']);
            $result = $assistant->analyze(
                $previewPath,
                ['title' => $asset->title],
                $requestedProvider,
            );

            $record->update([
                'status' => 'completed',
                'provider' => $result['provider'],
                'model' => $result['model'],
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
        } finally {
            $previews->delete($previewPath);
        }
    }

    private function extendExecutionTime(): void
    {
        $seconds = max(30, (int) config('ai-assets.request_max_execution_seconds', 360));

        try {
            if (function_exists('set_time_limit')) {
                set_time_limit($seconds);
            }

            ini_set('max_execution_time', (string) $seconds);
        } catch (Throwable) {
            // Some managed hosts disable runtime changes. Their PHP setting must be raised separately.
        }
    }

    public function apply(Request $request, Asset $asset, AiAssetSuggestion $suggestion, AssetTagService $tagService): RedirectResponse
    {
        abort_unless($suggestion->asset_id === $asset->id && $suggestion->status === 'completed', 404);

        $validated = $request->validate([
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['string', 'in:title,description,alt_text,seo_title,seo_description,keywords,dominant_colors,detected_objects'],
            'keyword_mode' => ['nullable', 'in:replace,append'],
            'keyword_names' => ['nullable', 'array', 'max:50'],
            'keyword_names.*' => ['string', 'max:100'],
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

            if ($field === 'keywords') {
                $names = $validated['keyword_names'] ?? Arr::wrap($value);
                if (($validated['keyword_mode'] ?? 'replace') === 'append') {
                    $tagService->mergeNames($asset, $names);
                } else {
                    $tagService->syncNames($asset, $names);
                }
                continue;
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
