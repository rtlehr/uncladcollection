<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\UserPrivacyPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyController extends Controller
{
    public function edit(Request $request): Response
    {
        $preference = UserPrivacyPreference::query()->firstOrCreate(['user_id' => $request->user()->id]);

        return Inertia::render('Account/Privacy/Index', [
            'preferences' => $preference->only(['personalized_recommendations', 'retain_recently_viewed', 'allow_unlisted_wish_lists']),
            'sharedWishLists' => $request->user()->wishLists()->where('visibility', 'unlisted')->count(),
            'dataSummary' => [
                'orders' => $request->user()->orders()->count(),
                'licenses' => $request->user()->licenses()->count(),
                'downloads' => $request->user()->downloads()->count(),
                'wish_lists' => $request->user()->wishLists()->count(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'personalized_recommendations' => ['required', 'boolean'],
            'retain_recently_viewed' => ['required', 'boolean'],
            'allow_unlisted_wish_lists' => ['required', 'boolean'],
        ]);

        UserPrivacyPreference::query()->updateOrCreate(['user_id' => $request->user()->id], $data);

        if (! $data['retain_recently_viewed']) {
            $request->user()->recentlyViewedAssets()->delete();
        }

        if (! $data['allow_unlisted_wish_lists']) {
            $request->user()->wishLists()->where('visibility', 'unlisted')->update([
                'visibility' => 'private',
                'share_token' => null,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Privacy preferences updated.']);

        return back();
    }
}
