<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetTrendingScore;
use App\Services\TrendingAssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrendingAssetController extends Controller
{
    public function index(Request $request): Response
    {
        $period = in_array($request->string('period')->toString(), ['now', 'week', 'month'], true)
            ? $request->string('period')->toString()
            : 'now';

        return Inertia::render('Admin/Discovery/Trending', [
            'period' => $period,
            'periods' => [
                ['value' => 'now', 'label' => 'Trending now'],
                ['value' => 'week', 'label' => 'This week'],
                ['value' => 'month', 'label' => 'This month'],
            ],
            'scores' => AssetTrendingScore::query()
                ->where('period', $period)
                ->with('asset:id,title,slug,asset_type,trending_boost,suppress_from_trending')
                ->orderBy('rank')
                ->paginate(50)
                ->withQueryString(),
        ]);
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $validated = $request->validate([
            'trending_boost' => ['required', 'integer', 'between:-100,100'],
            'suppress_from_trending' => ['required', 'boolean'],
        ]);

        $asset->update($validated);

        return back()->with('success', 'Trending controls updated. Rebuild rankings to apply the change.');
    }

    public function rebuild(Request $request, TrendingAssetService $service): RedirectResponse
    {
        $period = $request->validate(['period' => ['nullable', 'in:now,week,month']])['period'] ?? null;
        $service->rebuild($period);

        return back()->with('success', 'Trending rankings rebuilt.');
    }
}
