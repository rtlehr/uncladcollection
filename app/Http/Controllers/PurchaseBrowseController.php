<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseBrowseController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $sort = $request->string('sort', 'newest')->toString();

        $licenses = License::query()
            ->with(['image.collection', 'image.categories', 'image.tags', 'licenseType', 'order'])
            ->where('user_id', Auth::id())
            ->where('status', License::STATUS_ACTIVE)
            ->whereHas('image', function ($query) use ($search) {
                $query->where('is_active', true)
                    ->when($search, function ($query) use ($search) {
                        $query->where(function ($query) use ($search) {
                            $query->where('title', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%")
                                ->orWhere('photographer', 'like', "%{$search}%");
                        });
                    });
            })
            ->when($sort === 'oldest', fn ($query) => $query->oldest())
            ->when($sort === 'newest', fn ($query) => $query->latest())
            ->paginate(24)
            ->withQueryString()
            ->through(fn (License $license) => $this->formatPurchasedImageCard($license));

        return Inertia::render('Purchases/Index', [
            'licenses' => $licenses,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
            ],
        ]);
    }

    public function show(Image $image): Response
    {
        abort_unless($image->is_active, 404);

        $license = License::query()
            ->with(['image.collection', 'image.categories', 'image.tags', 'licenseType', 'order'])
            ->where('user_id', Auth::id())
            ->where('image_id', $image->id)
            ->where('status', License::STATUS_ACTIVE)
            ->latest()
            ->first();

        abort_unless($license, 403);

        $image->load(['collection', 'categories', 'tags']);

        return Inertia::render('Purchases/Show', [
            'licenseRecord' => $this->formatLicenseDetail($license),
        ]);
    }

    private function formatPurchasedImageCard(License $license): array
    {
        $image = $license->image;

        return [
            'id' => $license->id,
            'license_key' => $license->license_key,
            'license_name' => $license->license_name,
            'downloads_used' => $license->downloads_used,
            'download_limit' => $license->download_limit,
            'starts_at' => $license->starts_at?->format('Y-m-d'),
            'expires_at' => $license->expires_at?->format('Y-m-d'),

            'image' => [
                'id' => $image->id,
                'title' => $image->title,
                'slug' => $image->slug,
                'photographer' => $image->photographer,
                'thumbnail_url' => $image->thumbnail_path ? Storage::url($image->thumbnail_path) : null,
                'icon_url' => $image->icon_path ? Storage::url($image->icon_path) : null,
                'is_ai_generated' => $image->is_ai_generated,
                'favorites_count' => $image->favorites_count,
                'downloads_count' => $image->downloads_count,
                'purchases_count' => $image->purchases_count,
                'views_count' => $image->views_count,
            ],

            'order' => [
                'id' => $license->order?->id,
                'order_number' => $license->order?->order_number,
                'paid_at' => $license->order?->paid_at?->format('Y-m-d'),
                'total_formatted' => $license->order?->total_formatted,
            ],
        ];
    }

    private function formatLicenseDetail(License $license): array
    {
        $image = $license->image;

        return [
            'id' => $license->id,
            'license_key' => $license->license_key,
            'license_name' => $license->license_name,
            'license_terms' => $license->license_terms,
            'downloads_used' => $license->downloads_used,
            'download_limit' => $license->download_limit,
            'starts_at' => $license->starts_at?->format('Y-m-d'),
            'expires_at' => $license->expires_at?->format('Y-m-d'),
            'can_download' => $license->canDownload(),

            'image' => [
                'id' => $image->id,
                'title' => $image->title,
                'slug' => $image->slug,
                'description' => $image->description,
                'photographer' => $image->photographer,
                'thumbnail_url' => $image->thumbnail_path ? Storage::url($image->thumbnail_path) : null,
                'high_res_url' => $image->high_res_path ? Storage::url($image->high_res_path) : null,
                'original_url' => $image->original_path ? Storage::url($image->original_path) : null,
                'is_ai_generated' => $image->is_ai_generated,
                'created_at' => $image->created_at?->format('Y-m-d'),

                'collection' => $image->collection
                    ? [
                        'id' => $image->collection->id,
                        'name' => $image->collection->name,
                    ]
                    : null,

                'categories' => $image->categories
                    ->map(fn ($category) => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ])
                    ->values(),

                'tags' => $image->tags
                    ->map(fn ($tag) => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                    ])
                    ->values(),
            ],

            'order' => [
                'id' => $license->order?->id,
                'order_number' => $license->order?->order_number,
                'paid_at' => $license->order?->paid_at?->format('Y-m-d'),
                'total_formatted' => $license->order?->total_formatted,
            ],
        ];
    }
}