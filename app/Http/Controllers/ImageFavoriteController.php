<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\ImageFavorite;
use App\Services\WishListService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImageFavoriteController extends Controller
{
    public function store(Request $request, Image $image, WishListService $service): RedirectResponse
    {
        abort_unless($image->is_active, 404);

        if ($image->asset) {
            $service->add($request->user(), $service->defaultList($request->user()), $image->asset);

            return back()->with('success', 'Image added to Favorites.');
        }

        DB::transaction(function () use ($request, $image): void {
            ImageFavorite::query()->firstOrCreate([
                'user_id' => $request->user()->id,
                'image_id' => $image->id,
            ]);
            $image->updateQuietly(['favorites_count' => $image->favorites()->count()]);
        });

        return back()->with('success', 'Image added to Favorites.');
    }

    public function destroy(Request $request, Image $image, WishListService $service): RedirectResponse
    {
        if ($image->asset) {
            $service->remove($request->user(), $service->defaultList($request->user()), $image->asset);

            return back()->with('success', 'Image removed from Favorites.');
        }

        DB::transaction(function () use ($request, $image): void {
            ImageFavorite::query()
                ->where('user_id', $request->user()->id)
                ->where('image_id', $image->id)
                ->delete();
            $image->updateQuietly(['favorites_count' => $image->favorites()->count()]);
        });

        return back()->with('success', 'Image removed from Favorites.');
    }
}
