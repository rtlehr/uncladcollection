<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageDiscoverySection;
use App\Services\DiscoveryCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HomepageDiscoveryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Discovery/Homepage', [
            'sections' => HomepageDiscoverySection::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, HomepageDiscoverySection $section, DiscoveryCacheService $cache): RedirectResponse
    {
        $section->update($request->validate([
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'heading' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'item_limit' => ['required', 'integer', 'min:1', 'max:12'],
            'is_enabled' => ['required', 'boolean'],
            'audience' => ['required', Rule::in(['all', 'guest', 'authenticated'])],
        ]));
        $cache->invalidate();
        return back()->with('success', 'Homepage discovery section updated.');
    }
}
