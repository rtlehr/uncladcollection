<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\DiscoveryCollectionPlacement;
use App\Services\DiscoveryCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DiscoveryCollectionPlacementController extends Controller
{
    public function __construct(
        private readonly DiscoveryCacheService $cache,
    ) {}

    public function index(): Response
    {
        $placements = DiscoveryCollectionPlacement::query()
            ->with('collection:id,name,slug,is_active,cover_image_path')
            ->orderBy('placement')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (DiscoveryCollectionPlacement $placement) => [
                'id' => $placement->id,
                'collection_id' => $placement->collection_id,
                'collection' => $placement->collection,
                'placement' => $placement->placement,
                'content_type' => $placement->content_type,
                'audience' => $placement->audience,
                'eyebrow' => $placement->eyebrow,
                'heading' => $placement->heading,
                'description' => $placement->description,
                'call_to_action' => $placement->call_to_action,
                'sort_order' => $placement->sort_order,
                'starts_at' => $placement->starts_at?->format('Y-m-d\TH:i'),
                'ends_at' => $placement->ends_at?->format('Y-m-d\TH:i'),
                'is_active' => $placement->is_active,
                'status' => $placement->statusLabel(),
            ]);

        return Inertia::render('Admin/Discovery/CollectionPlacements', [
            'placements' => $placements,
            'collections' => Collection::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'options' => [
                'placements' => [
                    ['value' => 'homepage_primary', 'label' => 'Homepage — Primary'],
                    ['value' => 'homepage_secondary', 'label' => 'Homepage — Secondary'],
                ],
                'contentTypes' => [
                    ['value' => 'featured', 'label' => 'Featured'],
                    ['value' => 'seasonal', 'label' => 'Seasonal'],
                ],
                'audiences' => [
                    ['value' => 'all', 'label' => 'Everyone'],
                    ['value' => 'guest', 'label' => 'Guests'],
                    ['value' => 'authenticated', 'label' => 'Signed-in users'],
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        DiscoveryCollectionPlacement::query()->create($this->validated($request));
        $this->cache->invalidate();

        return back()->with('success', 'Discovery collection placement created.');
    }

    public function update(Request $request, DiscoveryCollectionPlacement $placement): RedirectResponse
    {
        $placement->update($this->validated($request));
        $this->cache->invalidate();

        return back()->with('success', 'Discovery collection placement updated.');
    }

    public function destroy(DiscoveryCollectionPlacement $placement): RedirectResponse
    {
        $placement->delete();
        $this->cache->invalidate();

        return back()->with('success', 'Discovery collection placement deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'collection_id' => ['required', 'integer', Rule::exists('collections', 'id')->where('is_active', true)],
            'placement' => ['required', Rule::in(['homepage_primary', 'homepage_secondary'])],
            'content_type' => ['required', Rule::in(['featured', 'seasonal'])],
            'audience' => ['required', Rule::in(['all', 'guest', 'authenticated'])],
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'heading' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'call_to_action' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
