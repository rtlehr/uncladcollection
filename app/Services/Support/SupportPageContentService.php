<?php

namespace App\Services\Support;

use App\Models\PublicPage;

class SupportPageContentService
{
    public function resolve(): array
    {
        $page = PublicPage::query()
            ->published()
            ->where('page_type', PublicPage::TYPE_SUPPORT)
            ->with('activeFaqItems')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if (! $page) {
            return $this->fallback();
        }

        return [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'eyebrow' => $page->eyebrow,
            'introduction' => $page->introduction,
            'content' => $page->content,
            'seo_title' => $page->seo_title,
            'seo_description' => $page->seo_description,
            'canonical_url' => $page->canonical_url,
            'header_image_url' => $page->header_image_url,
            'header_image_alt' => $page->header_image_alt,
            'faq_items' => $page->activeFaqItems
                ->map->only(['id', 'question', 'answer'])
                ->values(),
            'is_fallback' => false,
        ];
    }

    private function fallback(): array
    {
        return [
            'id' => null,
            'title' => 'Support Center',
            'slug' => null,
            'eyebrow' => 'Unclad Collection Support',
            'introduction' => 'Get help with your account, purchases, licenses, downloads, assets, advertising, or other questions.',
            'content' => null,
            'seo_title' => 'Support Center',
            'seo_description' => 'Submit and track support requests for Unclad Collection.',
            'canonical_url' => null,
            'header_image_url' => null,
            'header_image_alt' => null,
            'faq_items' => [],
            'is_fallback' => true,
        ];
    }
}
