<?php

namespace App\Http\Controllers;

use App\Models\PublicPage;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function show(string $slug): Response|RedirectResponse
    {
        $page=PublicPage::query()
            ->published()
            ->with('activeFaqItems')
            ->where('slug', $slug)
            ->firstOrFail();

        if ($page->page_type === PublicPage::TYPE_SUPPORT) {
            return redirect()->route('support.landing', status: 301);
        }
        return Inertia::render('PublicPages/Show',['publicPage'=>[
            'id'=>$page->id,'title'=>$page->title,'slug'=>$page->slug,'eyebrow'=>$page->eyebrow,'introduction'=>$page->introduction,
            'content'=>$page->content,'page_type'=>$page->page_type,'seo_title'=>$page->seo_title,'seo_description'=>$page->seo_description,
            'canonical_url'=>$page->canonical_url,'updated_at'=>$page->updated_at?->toISOString(),'header_image_url'=>$page->header_image_url,
            'header_image_alt'=>$page->header_image_alt,'legal_version'=>$page->legal_version,'effective_date'=>$page->effective_date?->toDateString(),
            'revised_date'=>$page->revised_date?->toDateString(),'faq_items'=>$page->activeFaqItems->map->only(['id','question','answer'])->values(),
        ]]);
    }
}
