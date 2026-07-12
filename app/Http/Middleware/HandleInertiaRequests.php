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

            'site' => fn () => $this->sitePayload(),

            'sidebarOpen' => ! $request->hasCookie('sidebar_state')
                || $request->cookie('sidebar_state') === 'true',

            'cart' => fn () => $this->cartPayload($user?->id),
        ];
    }

    private function sitePayload(): array
    {
        $settings = public_site_settings();

        return [
            'name' => data_get(
                $settings,
                'general.site_name',
                'Unclad Collection',
            ),
            'tagline' => data_get(
                $settings,
                'general.site_tagline',
            ),
            'contact_email' => data_get(
                $settings,
                'general.contact_email',
            ),
            'theme' => data_get(
                $settings,
                'branding.active_theme',
                'professional',
            ),
            'logo_url' => data_get(
                $settings,
                'branding.site_logo',
            ),
            'primary_color' => data_get(
                $settings,
                'branding.primary_color',
                '#1E2A38',
            ),
            'secondary_color' => data_get(
                $settings,
                'branding.secondary_color',
                '#50634D',
            ),
            'accent_color' => data_get(
                $settings,
                'branding.accent_color',
                '#D9824B',
            ),
            'footer_text' => data_get(
                $settings,
                'branding.footer_text',
                '© Unclad Collection. All rights reserved.',
            ),
            'social' => [
                'facebook_url' => data_get(
                    $settings,
                    'social.facebook_url',
                ),
                'instagram_url' => data_get(
                    $settings,
                    'social.instagram_url',
                ),
                'youtube_url' => data_get(
                    $settings,
                    'social.youtube_url',
                ),
                'pinterest_url' => data_get(
                    $settings,
                    'social.pinterest_url',
                ),
                'x_account_url' => data_get(
                    $settings,
                    'social.x_account_url',
                ),
            ],
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
