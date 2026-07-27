<?php

namespace App\Http\Controllers;

use App\Models\PublicPage;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function show(string $slug): Response
    {
        $page=PublicPage::query()->published()->where('slug',$slug)->firstOrFail();
        return Inertia::render('PublicPages/Show', ['publicPage'=>[
            'id'=>$page->id,'title'=>$page->title,'slug'=>$page->slug,'eyebrow'=>$page->eyebrow,
            'introduction'=>$page->introduction,'content'=>$page->content,'page_type'=>$page->page_type,
            'seo_title'=>$page->seo_title,'seo_description'=>$page->seo_description,
            'canonical_url'=>$page->canonical_url,'updated_at'=>$page->updated_at?->toISOString(),
        ]]);
    }
}
