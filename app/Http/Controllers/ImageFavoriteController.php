<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\ImageFavorite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ImageFavoriteController extends Controller
{
    public function store(Image $image): RedirectResponse
    {
        ImageFavorite::firstOrCreate([
            'user_id' => Auth::id(),
            'image_id' => $image->id,
        ]);

        $image->update([
            'favorites_count' => $image->favorites()->count(),
        ]);

        return back()->with('success', 'Image added to favorites.');
    }

    public function destroy(Image $image): RedirectResponse
    {
        ImageFavorite::query()
            ->where('user_id', Auth::id())
            ->where('image_id', $image->id)
            ->delete();

        $image->update([
            'favorites_count' => $image->favorites()->count(),
        ]);

        return back()->with('success', 'Image removed from favorites.');
    }
}