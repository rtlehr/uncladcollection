<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetFavorite;
use App\Services\AssetDiscoveryEligibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssetFavoriteController extends Controller
{
    public function store(Asset $asset, AssetDiscoveryEligibilityService $eligibility): RedirectResponse
    {
        abort_unless($eligibility->isDiscoverable($asset), 404);

        DB::transaction(function () use ($asset): void {
            AssetFavorite::firstOrCreate(['user_id' => Auth::id(), 'asset_id' => $asset->id]);
            $this->synchronizeCounts($asset);
        });

        return back()->with('success', 'Asset added to favorites.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        DB::transaction(function () use ($asset): void {
            AssetFavorite::query()->where('user_id', Auth::id())->where('asset_id', $asset->id)->delete();
            $this->synchronizeCounts($asset);
        });

        return back()->with('success', 'Asset removed from favorites.');
    }

    private function synchronizeCounts(Asset $asset): void
    {
        $count = $asset->favorites()->count();
        $asset->updateQuietly(['favorites_count' => $count]);

        if ($asset->legacyImage) {
            $asset->legacyImage->updateQuietly(['favorites_count' => $count]);
        }
    }
}
