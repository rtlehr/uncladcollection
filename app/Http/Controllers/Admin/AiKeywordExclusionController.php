<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiKeywordExclusion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AiKeywordExclusionController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));

        $items = AiKeywordExclusion::query()
            ->with('creator:id,name')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('keyword', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            }))
            ->orderBy('keyword')
            ->get()
            ->map(fn (AiKeywordExclusion $item) => [
                'id' => $item->id,
                'keyword' => $item->keyword,
                'is_active' => $item->is_active,
                'notes' => $item->notes,
                'created_by' => $item->creator?->name,
                'created_at' => $item->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/AiKeywordExclusions/Index', [
            'items' => $items,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $normalized = AiKeywordExclusion::normalize($validated['keyword']);

        AiKeywordExclusion::query()->updateOrCreate(
            ['normalized_keyword' => $normalized],
            [
                'keyword' => trim($validated['keyword']),
                'notes' => $validated['notes'] ?? null,
                'is_active' => true,
                'created_by' => $request->user()?->id,
            ],
        );

        return back()->with('success', 'Keyword exclusion saved.');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'keywords' => ['required', 'string', 'max:10000'],
        ]);

        $keywords = preg_split('/[\r\n,]+/', $validated['keywords']) ?: [];
        $count = 0;

        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if ($keyword === '') continue;

            $normalized = AiKeywordExclusion::normalize($keyword);
            AiKeywordExclusion::query()->updateOrCreate(
                ['normalized_keyword' => $normalized],
                ['keyword' => $keyword, 'is_active' => true, 'created_by' => $request->user()?->id],
            );
            $count++;
        }

        return back()->with('success', "{$count} keyword exclusion(s) saved.");
    }

    public function update(Request $request, AiKeywordExclusion $aiKeywordExclusion): RedirectResponse
    {
        $validated = $request->validate([
            'keyword' => ['sometimes', 'required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('keyword', $validated)) {
            $normalized = AiKeywordExclusion::normalize($validated['keyword']);
            validator(['normalized' => $normalized], [
                'normalized' => [Rule::unique('ai_keyword_exclusions', 'normalized_keyword')->ignore($aiKeywordExclusion->id)],
            ])->validate();
            $validated['normalized_keyword'] = $normalized;
        }

        $aiKeywordExclusion->update($validated);

        return back()->with('success', 'Keyword exclusion updated.');
    }

    public function destroy(AiKeywordExclusion $aiKeywordExclusion): RedirectResponse
    {
        $aiKeywordExclusion->delete();

        return back()->with('success', 'Keyword exclusion deleted.');
    }
}
