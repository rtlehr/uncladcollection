<?php

namespace App\Http\Controllers;

use App\Models\AssetFavorite;
use App\Models\Image;
use App\Models\ImageFavorite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ImageFavoriteController extends Controller
{
    public function store(Image $image): RedirectResponse
    {
        abort_unless($image->is_active, 404);

        DB::transaction(function () use ($image): void {
            ImageFavorite::firstOrCreate(['user_id' => Auth::id(), 'image_id' => $image->id]);

            if ($image->asset) {
                AssetFavorite::firstOrCreate(['user_id' => Auth::id(), 'asset_id' => $image->asset->id]);
            }

            $this->synchronizeCounts($image);
        });

        return back()->with('success', 'Image added to favorites.');
    }

    public function destroy(Image $image): RedirectResponse
    {
        DB::transaction(function () use ($image): void {
            ImageFavorite::query()->where('user_id', Auth::id())->where('image_id', $image->id)->delete();

            if ($image->asset) {
                AssetFavorite::query()->where('user_id', Auth::id())->where('asset_id', $image->asset->id)->delete();
            }

            $this->synchronizeCounts($image);
        });

        return back()->with('success', 'Image removed from favorites.');
    }

    private function synchronizeCounts(Image $image): void
    {
        if ($image->asset) {
            $count = $image->asset->favorites()->count();
            $image->asset->updateQuietly(['favorites_count' => $count]);
            $image->updateQuietly(['favorites_count' => $count]);

            return;
        }

        $image->updateQuietly(['favorites_count' => $image->favorites()->count()]);
    }
}
