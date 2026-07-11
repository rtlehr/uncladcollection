<?php

namespace App\Http\Middleware;

use App\Models\CartItem;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'name' => config('app.name'),

            'auth' => fn () => [
                'user' => $user
                    ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'email' => $user->email,
                        'roles' => $user->roles()
                            ->orderBy('label')
                            ->pluck('label')
                            ->all(),
                        'role_names' => $user->roleNames(),
                        'permissions' => $user->allPermissionNames(),
                        'avatar_url' => $user->avatar_url,
                    ]
                    : null,
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],

            'site' => fn () => [
                'name' => site_setting('general.site_name', 'Unclad Collection'),
                'tagline' => site_setting('general.site_tagline'),
                'theme' => site_setting('branding.active_theme', 'professional'),
            ],

            'sidebarOpen' => ! $request->hasCookie('sidebar_state')
                || $request->cookie('sidebar_state') === 'true',

            'cart' => fn () => $this->cartPayload($user?->id),
        ];
    }

    private function cartPayload(?int $userId): array
    {
        if (! $userId) {
            return [
                'count' => 0,
                'items' => [],
            ];
        }

        $query = CartItem::query()
            ->where('user_id', $userId);

        $count = (clone $query)->count();

        $items = $query
            ->select([
                'id',
                'image_id',
                'license_type_id',
                'price_cents',
                'created_at',
            ])
            ->with([
                'image:id,title,slug,thumbnail_path,icon_path',
                'licenseType:id,name',
            ])
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
            ])
            ->values();

        return [
            'count' => $count,
            'items' => $items,
        ];
    }
}
