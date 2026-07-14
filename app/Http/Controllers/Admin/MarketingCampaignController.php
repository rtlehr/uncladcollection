<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingCampaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MarketingCampaignController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/MarketingCampaigns/Index', [
            'campaigns' => MarketingCampaign::query()
                ->orderBy('sort_order')
                ->latest()
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/MarketingCampaigns/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCampaign($request, true);
        $uuid = (string) Str::uuid();
        $validated['uuid'] = $uuid;
        $validated['media_path'] = $request->file('media')->store("marketing/campaigns/{$uuid}", 'public');
        $validated['poster_path'] = $request->file('poster')?->store("marketing/campaigns/{$uuid}", 'public');
        $validated = $this->normalizeBooleans($request, $validated);
        MarketingCampaign::create($validated);

        return redirect()->route('admin.marketing-campaigns.index')
            ->with('success', 'Marketing campaign created.');
    }

    public function edit(MarketingCampaign $marketingCampaign): Response
    {
        return Inertia::render('Admin/MarketingCampaigns/Edit', [
            'campaign' => $marketingCampaign,
        ]);
    }

    public function update(Request $request, MarketingCampaign $marketingCampaign): RedirectResponse
    {
        $validated = $this->validateCampaign($request, false);
        $directory = "marketing/campaigns/{$marketingCampaign->uuid}";

        if ($request->hasFile('media')) {
            Storage::disk('public')->delete($marketingCampaign->media_path);
            $validated['media_path'] = $request->file('media')->store($directory, 'public');
        }

        if ($request->boolean('remove_poster')) {
            Storage::disk('public')->delete($marketingCampaign->poster_path);
            $validated['poster_path'] = null;
        } elseif ($request->hasFile('poster')) {
            Storage::disk('public')->delete($marketingCampaign->poster_path);
            $validated['poster_path'] = $request->file('poster')->store($directory, 'public');
        }

        $marketingCampaign->update($this->normalizeBooleans($request, $validated));

        return redirect()->route('admin.marketing-campaigns.index')
            ->with('success', 'Marketing campaign updated.');
    }

    public function destroy(MarketingCampaign $marketingCampaign): RedirectResponse
    {
        Storage::disk('public')->deleteDirectory("marketing/campaigns/{$marketingCampaign->uuid}");
        $marketingCampaign->delete();

        return redirect()->route('admin.marketing-campaigns.index')
            ->with('success', 'Marketing campaign deleted.');
    }

    private function validateCampaign(Request $request, bool $mediaRequired): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'media_type' => ['required', Rule::in(['image', 'video'])],
            'media' => [$mediaRequired ? 'required' : 'nullable', 'file', 'max:102400', 'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm'],
            'poster' => ['nullable', 'image', 'max:20480'],
            'remove_poster' => ['nullable', 'boolean'],
            'eyebrow' => ['nullable', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:255'],
            'subheadline' => ['nullable', 'string', 'max:2000'],
            'primary_button_label' => ['nullable', 'string', 'max:100'],
            'primary_button_url' => ['nullable', 'string', 'max:500'],
            'secondary_button_label' => ['nullable', 'string', 'max:100'],
            'secondary_button_url' => ['nullable', 'string', 'max:500'],
            'overlay_opacity' => ['required', 'integer', 'min:0', 'max:90'],
            'media_position' => ['required', Rule::in(['center', 'top', 'bottom', 'left', 'right'])],
            'hero_height' => ['required', Rule::in(['compact', 'medium', 'large', 'fullscreen'])],
            'text_alignment' => ['required', Rule::in(['left', 'center', 'right'])],
            'autoplay_first_visit' => ['nullable', 'boolean'],
            'autoplay_mobile' => ['nullable', 'boolean'],
            'loop_video' => ['nullable', 'boolean'],
            'show_search' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
    }

    private function normalizeBooleans(Request $request, array $validated): array
    {
        foreach (['autoplay_first_visit', 'autoplay_mobile', 'loop_video', 'show_search', 'is_active'] as $field) {
            $validated[$field] = $request->boolean($field);
        }
        unset($validated['media'], $validated['poster'], $validated['remove_poster']);
        return $validated;
    }
}
