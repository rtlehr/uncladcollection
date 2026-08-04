<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiFeatureAssignment;
use App\Models\AiProvider;
use App\Services\Ai\AiFeatureCatalog;
use App\Services\Ai\AiTextGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AiProviderController extends Controller
{
    public function index(): Response
    {
        $providers = AiProvider::query()->orderBy('name')->get()->map(fn (AiProvider $provider) => [
            'id' => $provider->id, 'name' => $provider->name, 'slug' => $provider->slug,
            'driver' => $provider->driver, 'base_url' => $provider->base_url,
            'api_key_masked' => $provider->maskedKey(), 'default_model' => $provider->default_model,
            'connect_timeout_seconds' => $provider->connect_timeout_seconds,
            'timeout_seconds' => $provider->timeout_seconds, 'retry_times' => $provider->retry_times,
            'streaming_enabled' => $provider->streaming_enabled, 'is_enabled' => $provider->is_enabled,
            'last_tested_at' => $provider->last_tested_at?->toIso8601String(),
            'last_test_status' => $provider->last_test_status, 'last_test_message' => $provider->last_test_message,
        ]);

        $assignments = AiFeatureAssignment::query()->get()->keyBy('feature');

        return Inertia::render('Admin/AiProviders/Index', [
            'providers' => $providers,
            'features' => collect(AiFeatureCatalog::FEATURES)->map(fn ($label, $key) => [
                'key' => $key, 'label' => $label,
                'assignment' => optional($assignments->get($key))->only(['primary_provider_id','primary_model','fallback_provider_id','fallback_model','fallback_enabled']) ?? [],
            ])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedProvider($request);
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);
        $data['updated_by_user_id'] = $request->user()?->id;
        AiProvider::query()->create($data);
        return back()->with('success', 'AI provider created.');
    }

    public function update(Request $request, AiProvider $aiProvider): RedirectResponse
    {
        $data = $this->validatedProvider($request, $aiProvider);
        if (blank($data['api_key'] ?? null)) unset($data['api_key']);
        $data['updated_by_user_id'] = $request->user()?->id;
        $aiProvider->update($data);
        return back()->with('success', 'AI provider updated.');
    }

    public function destroy(AiProvider $aiProvider): RedirectResponse
    {
        abort_if(AiFeatureAssignment::query()->where('primary_provider_id', $aiProvider->id)->orWhere('fallback_provider_id', $aiProvider->id)->exists(), 422, 'Remove this provider from feature assignments before deleting it.');
        $aiProvider->delete();
        return back()->with('success', 'AI provider deleted.');
    }

    public function saveAssignments(Request $request): RedirectResponse
    {
        $data = $request->validate(['assignments' => ['required','array'], 'assignments.*.feature' => ['required','in:'.implode(',', array_keys(AiFeatureCatalog::FEATURES))], 'assignments.*.primary_provider_id' => ['required','exists:ai_providers,id'], 'assignments.*.primary_model' => ['nullable','string','max:255'], 'assignments.*.fallback_provider_id' => ['nullable','different:assignments.*.primary_provider_id','exists:ai_providers,id'], 'assignments.*.fallback_model' => ['nullable','string','max:255'], 'assignments.*.fallback_enabled' => ['boolean']]);
        foreach ($data['assignments'] as $row) {
            AiFeatureAssignment::query()->updateOrCreate(['feature' => $row['feature']], [...$row, 'updated_by_user_id' => $request->user()?->id]);
        }
        return back()->with('success', 'AI feature assignments saved.');
    }

    public function test(AiProvider $aiProvider, AiTextGateway $gateway): JsonResponse
    {
        $result = $gateway->test($aiProvider);
        $aiProvider->update(['last_tested_at' => now(), 'last_test_status' => $result['success'] ? 'success' : 'failed', 'last_test_message' => $result['message']]);
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function models(AiProvider $aiProvider, AiTextGateway $gateway): JsonResponse
    {
        return response()->json(['models' => $gateway->listModels($aiProvider)]);
    }

    private function validatedProvider(Request $request, ?AiProvider $provider = null): array
    {
        return $request->validate([
            'name' => ['required','string','max:120'], 'slug' => ['nullable','string','max:120','unique:ai_providers,slug,'.($provider?->id ?? 'NULL')],
            'driver' => ['required','in:ollama,venice,openai'], 'base_url' => ['required','url','max:500'],
            'api_key' => ['nullable','string','max:4000'], 'default_model' => ['nullable','string','max:255'],
            'connect_timeout_seconds' => ['required','integer','min:5','max:120'], 'timeout_seconds' => ['required','integer','min:30','max:1200'],
            'retry_times' => ['required','integer','min:0','max:10'], 'streaming_enabled' => ['boolean'], 'is_enabled' => ['boolean'],
        ]);
    }
}
