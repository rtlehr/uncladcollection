<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('home') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ route('images.index') }}</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>{{ route('blog.index') }}</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
@foreach ($collections as $collection)
    <url>
        <loc>{{ route('collections.show', $collection->slug) }}</loc>
        <lastmod>{{ $collection->updated_at?->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
@endforeach
</urlset>
