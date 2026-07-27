<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePublicPageRequest;
use App\Http\Requests\Admin\UpdatePublicPageRequest;
use App\Models\PublicPage;
use App\Services\AdminActivityService;
use App\Services\PublicPages\PublicPageMediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function __construct(private readonly AdminActivityService $activity, private readonly PublicPageMediaService $media) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PublicPage::class);
        $pages = PublicPage::query()->when($request->string('search')->toString(), fn ($q,$s) => $q->where(fn($x)=>$x->where('title','like',"%{$s}%")->orWhere('slug','like',"%{$s}%")))
            ->when($request->string('status')->toString(), fn ($q,$s) => $q->where('status',$s))->orderBy('sort_order')->orderBy('title')->paginate(20)->withQueryString();
        return Inertia::render('Admin/PublicPages/Index', ['pages'=>$pages,'filters'=>$request->only(['search','status'])]);
    }

    public function create(): Response { $this->authorize('create', PublicPage::class); return Inertia::render('Admin/PublicPages/Create', $this->options()); }

    public function store(StorePublicPageRequest $request): RedirectResponse
    {
        $data=$request->validated(); $this->assertPublishPermission($request,$data['status']);
        $page = DB::transaction(function () use ($request,$data): PublicPage {
            $pageData=$this->pageData($request,$data); $pageData['created_by_user_id']=Auth::id(); $pageData['updated_by_user_id']=Auth::id();
            $page=PublicPage::create($pageData); $this->saveMedia($request,$page,$data); $this->syncFaqs($page,$data['faq_items']??[]); return $page->refresh();
        });
        $this->activity->created(subject:$page, description:'Created public page.');
        return redirect()->route('admin.public-pages.index')->with('success','Public page created.');
    }

    public function edit(PublicPage $publicPage): Response
    {
        $this->authorize('update',$publicPage); $publicPage->load('faqItems');
        return Inertia::render('Admin/PublicPages/Edit', [...$this->options(), 'publicPage'=>$publicPage]);
    }

    public function update(UpdatePublicPageRequest $request, PublicPage $publicPage): RedirectResponse
    {
        $data=$request->validated(); $this->assertPublishPermission($request,$data['status']);
        DB::transaction(function () use ($request,$data,$publicPage): void {
            $pageData=$this->pageData($request,$data,$publicPage); $pageData['updated_by_user_id']=Auth::id(); $publicPage->update($pageData);
            $this->saveMedia($request,$publicPage,$data); $this->syncFaqs($publicPage,$data['faq_items']??[]);
        });
        $this->activity->log(action:'updated',subject:$publicPage,description:'Updated public page.');
        return redirect()->route('admin.public-pages.index')->with('success','Public page updated.');
    }

    public function destroy(PublicPage $publicPage): RedirectResponse
    {
        $this->authorize('delete',$publicPage); $this->media->deleteDirectory($publicPage); $publicPage->delete();
        $this->activity->deleted(subject:$publicPage,description:'Deleted public page.');
        return redirect()->route('admin.public-pages.index')->with('success','Public page deleted.');
    }

    private function pageData(Request $request,array $data,?PublicPage $page=null): array
    {
        return Arr::except(array_merge($data,[
            'is_active'=>$request->boolean('is_active',true),
            'published_at'=>$data['status']===PublicPage::STATUS_PUBLISHED?($data['published_at']??$page?->published_at??now()):null,
        ]),['header_image_original','header_image_rendered','header_image_edit','remove_header_image','faq_items']);
    }

    private function saveMedia(Request $request,PublicPage $page,array $data): void
    {
        if ($request->boolean('remove_header_image')) {
            $this->media->delete($page->header_image_original_path); $this->media->delete($page->header_image_path);
            $page->update(['header_image_original_path'=>null,'header_image_path'=>null,'header_image_edit'=>null,'header_image_alt'=>null]); return;
        }
        $updates=[];
        if ($request->hasFile('header_image_original')) { $this->media->delete($page->header_image_original_path); $updates['header_image_original_path']=$this->media->storeOriginal($request->file('header_image_original'),$page); }
        if ($request->hasFile('header_image_rendered')) { $this->media->delete($page->header_image_path); $updates['header_image_path']=$this->media->storeRendered($request->file('header_image_rendered'),$page); }
        if (isset($data['header_image_edit'])) $updates['header_image_edit']=$data['header_image_edit'];
        if (array_key_exists('header_image_alt',$data)) $updates['header_image_alt']=$data['header_image_alt'];
        if ($updates!==[]) $page->update($updates);
    }

    private function syncFaqs(PublicPage $page,array $items): void
    {
        $page->faqItems()->delete();
        foreach (array_values($items) as $index=>$item) $page->faqItems()->create(['question'=>$item['question'],'answer'=>$item['answer'],'is_active'=>(bool)($item['is_active']??true),'sort_order'=>(int)($item['sort_order']??(($index+1)*10))]);
    }

    private function options(): array { return ['types'=>config('public-pages.types'),'navigationLocations'=>config('public-pages.navigation_locations'),'statuses'=>[PublicPage::STATUS_DRAFT,PublicPage::STATUS_PUBLISHED]]; }
    private function assertPublishPermission(Request $request,string $status): void { if($status===PublicPage::STATUS_PUBLISHED&&!$request->user()?->hasPermission('publish_public_pages')) abort(403); }
}
