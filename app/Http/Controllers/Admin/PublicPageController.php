<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePublicPageRequest;
use App\Http\Requests\Admin\UpdatePublicPageRequest;
use App\Models\PublicPage;
use App\Services\AdminActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function __construct(private readonly AdminActivityService $activity) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PublicPage::class);
        $pages = PublicPage::query()
            ->when($request->string('search')->toString(), fn ($q,$s) => $q->where(fn($x)=>$x->where('title','like',"%{$s}%")->orWhere('slug','like',"%{$s}%")))
            ->when($request->string('status')->toString(), fn ($q,$s) => $q->where('status',$s))
            ->orderBy('sort_order')->orderBy('title')->paginate(20)->withQueryString();
        return Inertia::render('Admin/PublicPages/Index', ['pages'=>$pages,'filters'=>$request->only(['search','status'])]);
    }

    public function create(): Response
    {
        $this->authorize('create', PublicPage::class);
        return Inertia::render('Admin/PublicPages/Create', $this->options());
    }

    public function store(StorePublicPageRequest $request): RedirectResponse
    {
        $data=$request->validated();
        $this->assertPublishPermission($request, $data['status']);
        $data['created_by_user_id']=Auth::id(); $data['updated_by_user_id']=Auth::id();
        $data['is_active']=$request->boolean('is_active', true);
        $data['published_at']=$data['status']===PublicPage::STATUS_PUBLISHED ? ($data['published_at'] ?? now()) : null;
        $page=PublicPage::create($data);
        $this->activity->created(subject:$page, description:'Created public page.');
        return redirect()->route('admin.public-pages.index')->with('success','Public page created.');
    }

    public function edit(PublicPage $publicPage): Response
    {
        $this->authorize('update',$publicPage);
        return Inertia::render('Admin/PublicPages/Edit', [...$this->options(), 'publicPage'=>$publicPage]);
    }

    public function update(UpdatePublicPageRequest $request, PublicPage $publicPage): RedirectResponse
    {
        $data=$request->validated(); $this->assertPublishPermission($request,$data['status']);
        $data['updated_by_user_id']=Auth::id(); $data['is_active']=$request->boolean('is_active',true);
        $data['published_at']=$data['status']===PublicPage::STATUS_PUBLISHED ? ($data['published_at'] ?? $publicPage->published_at ?? now()) : null;
        $publicPage->update($data);
        $this->activity->log(action: 'updated', subject: $publicPage, description: 'Updated public page.');
        return redirect()->route('admin.public-pages.index')->with('success','Public page updated.');
    }

    public function destroy(PublicPage $publicPage): RedirectResponse
    {
        $this->authorize('delete',$publicPage); $publicPage->delete();
        $this->activity->deleted(subject:$publicPage, description:'Deleted public page.');
        return redirect()->route('admin.public-pages.index')->with('success','Public page deleted.');
    }

    private function options(): array
    {
        return ['types'=>config('public-pages.types'),'navigationLocations'=>config('public-pages.navigation_locations'),'statuses'=>[PublicPage::STATUS_DRAFT,PublicPage::STATUS_PUBLISHED]];
    }

    private function assertPublishPermission(Request $request, string $status): void
    {
        if ($status===PublicPage::STATUS_PUBLISHED && ! $request->user()?->hasPermission('publish_public_pages')) abort(403);
    }
}
