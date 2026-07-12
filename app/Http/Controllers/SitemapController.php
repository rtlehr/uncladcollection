<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Collection;
use App\Models\Image;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = Cache::remember(
            'seo.sitemap.index',
            now()->addHours(6),
            fn () => view('sitemaps.index', [
                'sitemaps' => [
                    [
                        'loc' => route('sitemap.pages'),
                        'lastmod' => now()->toAtomString(),
                    ],
                    [
                        'loc' => route('sitemap.images'),
                        'lastmod' => $this->lastModified(
                            Image::query()
                                ->where('is_active', true)
                                ->max('updated_at'),
                        ),
                    ],
                    [
                        'loc' => route('sitemap.articles'),
                        'lastmod' => $this->lastModified(
                            BlogPost::query()
                                ->published()
                                ->max('updated_at'),
                        ),
                    ],
                ],
            ])->render(),
        );

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private function lastModified(mixed $value): ?string
    {
        return $value
            ? Carbon::parse($value)->toAtomString()
            : null;
    }

    public function pages(): Response
    {
        $collections = Collection::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get([
                'slug',
                'updated_at',
            ]);

        return response(
            view('sitemaps.pages', compact('collections'))->render(),
            200,
            ['Content-Type' => 'application/xml; charset=UTF-8'],
        );
    }

    public function images(): Response
    {
        $images = Image::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get([
                'title',
                'slug',
                'description',
                'photographer',
                'high_res_path',
                'thumbnail_path',
                'icon_path',
                'updated_at',
            ]);

        return response(
            view('sitemaps.images', compact('images'))->render(),
            200,
            ['Content-Type' => 'application/xml; charset=UTF-8'],
        );
    }

    public function articles(): Response
    {
        $articles = BlogPost::query()
            ->published()
            ->orderBy('id')
            ->get([
                'title',
                'slug',
                'published_at',
                'updated_at',
            ]);

        return response(
            view('sitemaps.articles', compact('articles'))->render(),
            200,
            ['Content-Type' => 'application/xml; charset=UTF-8'],
        );
    }
}
