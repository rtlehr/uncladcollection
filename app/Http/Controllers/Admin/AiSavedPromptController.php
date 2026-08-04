<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiSavedPrompt;
use App\Models\AiSavedPromptVersion;
use App\Services\Ai\ContentStudio\ImagePromptGenerator;
use App\Services\Ai\ContentStudio\SavedPromptRefiner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AiSavedPromptController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'sort' => ['nullable', Rule::in(['title','created_at','updated_at','content_context','intended_use'])],
            'direction' => ['nullable', Rule::in(['asc','desc'])],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $sort = (string) ($validated['sort'] ?? 'updated_at');
        $direction = (string) ($validated['direction'] ?? 'desc');

        $items = AiSavedPrompt::query()
            ->with(['updater:id,name'])
            ->withCount('versions')
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('prompt_text', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            }))
            ->orderBy($sort, $direction)
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/AiContent/SavedPrompts/Index', [
            'items' => $items,
            'filters' => compact('search', 'sort', 'direction'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/AiContent/SavedPrompts/Create');
    }

    public function generate(Request $request, ImagePromptGenerator $generator): JsonResponse
    {
        $validated = $this->validatePromptSettings($request);
        $generation = $generator->generate($validated, $request->user()?->id);

        return response()->json([
            'prompt' => $generation->output_text,
            'generation_id' => $generation->id,
            'provider' => $generation->provider,
            'model' => $generation->model,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateSavedPrompt($request);
        $userId = $request->user()?->id;

        $prompt = DB::transaction(function () use ($validated, $userId) {
            $prompt = AiSavedPrompt::create([...$validated, 'created_by' => $userId, 'updated_by' => $userId]);
            $prompt->versions()->create([
                'version_number' => 1,
                'prompt_text' => $prompt->prompt_text,
                'provider' => $prompt->provider,
                'model' => $prompt->model,
                'created_by' => $userId,
            ]);
            return $prompt;
        });

        return redirect()->route('admin.ai-content.image-prompts.edit', $prompt)
            ->with('success', 'Prompt saved.');
    }

    public function edit(AiSavedPrompt $aiSavedPrompt): Response
    {
        $aiSavedPrompt->load(['versions.creator:id,name']);

        return Inertia::render('Admin/AiContent/SavedPrompts/Edit', [
            'savedPrompt' => $aiSavedPrompt,
        ]);
    }

    public function update(Request $request, AiSavedPrompt $aiSavedPrompt): RedirectResponse
    {
        $validated = $this->validateSavedPrompt($request);
        $instruction = trim((string) $request->input('refinement_instruction', ''));
        $userId = $request->user()?->id;

        DB::transaction(function () use ($aiSavedPrompt, $validated, $instruction, $userId) {
            $promptChanged = $aiSavedPrompt->prompt_text !== $validated['prompt_text'];
            $aiSavedPrompt->update([...$validated, 'updated_by' => $userId]);

            if ($promptChanged) {
                $nextVersion = ((int) $aiSavedPrompt->versions()->max('version_number')) + 1;
                $aiSavedPrompt->versions()->create([
                    'version_number' => $nextVersion,
                    'prompt_text' => $validated['prompt_text'],
                    'refinement_instruction' => $instruction !== '' ? $instruction : null,
                    'provider' => $validated['provider'] ?? null,
                    'model' => $validated['model'] ?? null,
                    'created_by' => $userId,
                ]);
            }
        });

        return back()->with('success', 'Prompt updated.');
    }

    public function refine(Request $request, AiSavedPrompt $aiSavedPrompt, SavedPromptRefiner $refiner): JsonResponse
    {
        $validated = $request->validate([
            'prompt_text' => ['required', 'string', 'max:50000'],
            'instruction' => ['required', 'string', 'max:3000'],
            'content_context' => ['required', Rule::in(['general','adult_naturism','family_naturism'])],
            'output_mode' => ['required', Rule::in(['content_only','content_composition','full'])],
            'body_detail_level' => ['required', Rule::in(['contextual','natural_detail','detailed_adult_anatomy'])],
            'description_depth' => ['required', Rule::in(['compact','standard','detailed','expanded'])],
            'character_detail_level' => ['required', Rule::in(['minimal','standard','detailed','very_detailed'])],
            'environment_detail_level' => ['required', Rule::in(['minimal','standard','detailed','rich'])],
        ]);

        $result = $refiner->refine($validated['prompt_text'], $validated['instruction'], $validated);

        return response()->json([
            'prompt' => $result['content'],
            'provider' => $result['provider'],
            'model' => $result['model'],
        ]);
    }

    public function restore(Request $request, AiSavedPrompt $aiSavedPrompt, AiSavedPromptVersion $version): RedirectResponse
    {
        abort_unless($version->ai_saved_prompt_id === $aiSavedPrompt->id, 404);
        $userId = $request->user()?->id;

        DB::transaction(function () use ($aiSavedPrompt, $version, $userId) {
            $nextVersion = ((int) $aiSavedPrompt->versions()->max('version_number')) + 1;
            $aiSavedPrompt->update([
                'prompt_text' => $version->prompt_text,
                'provider' => $version->provider,
                'model' => $version->model,
                'updated_by' => $userId,
            ]);
            $aiSavedPrompt->versions()->create([
                'version_number' => $nextVersion,
                'prompt_text' => $version->prompt_text,
                'refinement_instruction' => "Restored from version {$version->version_number}",
                'provider' => $version->provider,
                'model' => $version->model,
                'created_by' => $userId,
            ]);
        });

        return back()->with('success', "Version {$version->version_number} restored.");
    }

    public function destroy(AiSavedPrompt $aiSavedPrompt): RedirectResponse
    {
        $aiSavedPrompt->delete();
        return redirect()->route('admin.ai-content.image-prompts.index')->with('success', 'Prompt deleted.');
    }

    private function validatePromptSettings(Request $request): array
    {
        return $request->validate([
            'description' => ['required','string','max:5000'],
            'intended_use' => ['required', Rule::in(['general_image','blog_header','blog_inline','collection_cover','category_banner','advertisement','social_media','email_graphic','landing_page'])],
            'content_context' => ['required', Rule::in(['general','adult_naturism','family_naturism'])],
            'output_mode' => ['required', Rule::in(['content_only','content_composition','full'])],
            'body_detail_level' => ['required', Rule::in(['contextual','natural_detail','detailed_adult_anatomy'])],
            'description_depth' => ['required', Rule::in(['compact','standard','detailed','expanded'])],
            'character_detail_level' => ['required', Rule::in(['minimal','standard','detailed','very_detailed'])],
            'environment_detail_level' => ['required', Rule::in(['minimal','standard','detailed','rich'])],
            'describe_every_visible_person' => ['required','boolean'],
            'orientation' => ['required', Rule::in(['landscape','portrait','square','auto'])],
            'additional_instructions' => ['nullable','string','max:3000'],
        ]);
    }

    private function validateSavedPrompt(Request $request): array
    {
        return $request->validate([
            'title' => ['required','string','max:255'],
            'description' => ['nullable','string','max:5000'],
            'prompt_text' => ['required','string','max:50000'],
            'intended_use' => ['required', Rule::in(['general_image','blog_header','blog_inline','collection_cover','category_banner','advertisement','social_media','email_graphic','landing_page'])],
            'content_context' => ['required', Rule::in(['general','adult_naturism','family_naturism'])],
            'output_mode' => ['required', Rule::in(['content_only','content_composition','full'])],
            'body_detail_level' => ['required', Rule::in(['contextual','natural_detail','detailed_adult_anatomy'])],
            'description_depth' => ['required', Rule::in(['compact','standard','detailed','expanded'])],
            'character_detail_level' => ['required', Rule::in(['minimal','standard','detailed','very_detailed'])],
            'environment_detail_level' => ['required', Rule::in(['minimal','standard','detailed','rich'])],
            'describe_every_visible_person' => ['required','boolean'],
            'orientation' => ['required', Rule::in(['landscape','portrait','square','auto'])],
            'additional_instructions' => ['nullable','string','max:3000'],
            'provider' => ['nullable','string','max:100'],
            'model' => ['nullable','string','max:150'],
            'source_generation_id' => ['nullable','integer','exists:ai_generations,id'],
        ]);
    }
}
