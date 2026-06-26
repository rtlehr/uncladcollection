<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\CartItem;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'name' => config('app.name'),

            'auth' => [
                'user' => $request->user()
                    ? [
                        'id' => $request->user()->id,
                        'name' => $request->user()->name,
                        'username' => $request->user()?->username,
                        'email' => $request->user()->email,
                        'roles' => $request->user()
                            ->roles()
                            ->orderBy('label')
                            ->pluck('label')
                            ->toArray(),

                        'role_names' => $request->user()->roleNames(),
                        'permissions' => $request->user()->allPermissionNames(),
                        'avatar_url' => $request->user()?->avatar_url,
                    ]
                    : null,
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],

            'site' => [
                'name' => site_setting('general.site_name', 'Unclad Collection'),
                'tagline' => site_setting('general.site_tagline'),
                'theme' => site_setting('branding.active_theme', 'professional'),
            ],

            'sidebarOpen' => ! $request->hasCookie('sidebar_state')
                        || $request->cookie('sidebar_state') === 'true',

                    'cart' => fn () => $request->user()
            ? [
                'count' => CartItem::query()
                    ->where('user_id', $request->user()->id)
                    ->count(),

                'items' => CartItem::query()
                    ->with(['image', 'licenseType'])
                    ->where('user_id', $request->user()->id)
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(fn (CartItem $cartItem) => [
                        'id' => $cartItem->id,
                        'price_cents' => $cartItem->price_cents,
                        'image' => [
                            'title' => $cartItem->image?->title,
                            'slug' => $cartItem->image?->slug,
                            'thumbnail_url' => $cartItem->image?->thumbnail_url,
                            'icon_url' => $cartItem->image?->icon_url,
                        ],
                        'license_type' => [
                            'name' => $cartItem->licenseType?->name,
                        ],
                    ]),
            ]
            : [
                'count' => 0,
                'items' => [],
            ],
        ];
    }
}