<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialPostPromptAddition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SocialPostPromptAdditionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:social_post_prompt_additions,name'],
            'category' => ['nullable', 'string', 'max:80'],
            'prompt_text' => ['required', 'string', 'max:10000'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        SocialPostPromptAddition::create([
            ...$data,
            'category' => $this->cleanOptional($data['category'] ?? null),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Prompt addition created.');
    }

    public function update(Request $request, SocialPostPromptAddition $promptAddition): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('social_post_prompt_additions', 'name')->ignore($promptAddition->id)],
            'category' => ['nullable', 'string', 'max:80'],
            'prompt_text' => ['required', 'string', 'max:10000'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $promptAddition->update([
            ...$data,
            'category' => $this->cleanOptional($data['category'] ?? null),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Prompt addition updated. Future generations will use the new text.');
    }

    public function destroy(SocialPostPromptAddition $promptAddition): RedirectResponse
    {
        $promptAddition->delete();

        return back()->with('success', 'Prompt addition deleted. Historical AI suggestions keep their saved prompt snapshot.');
    }

    private function cleanOptional(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value !== '' ? $value : null;
    }
}
