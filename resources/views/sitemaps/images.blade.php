<?xml version="1.0" encoding="UTF-8"?>
<urlset
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
>
@foreach ($images as $image)
    @php
        $imageUrl = $image->high_res_url
            ?? $image->thumbnail_url
            ?? $image->icon_url;
    @endphp
    <url>
        <loc>{{ route('images.show', $image->slug) }}</loc>
        <lastmod>{{ $image->updated_at?->toAtomString() }}</lastmod>
        @if ($imageUrl)
            <image:image>
                <image:loc>{{ url($imageUrl) }}</image:loc>
                <image:title>{{ $image->title }}</image:title>
                @if ($image->description)
                    <image:caption>{{ $image->description }}</image:caption>
                @endif
                @if ($image->photographer)
                    <image:license>{{ url('/licensing') }}</image:license>
                @endif
            </image:image>
        @endif
    </url>
@endforeach
</urlset>
