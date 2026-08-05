<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\DesignProject;
use App\Models\License;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DesignProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $projects = DesignProject::query()->where('user_id',$request->user()->id)->with(['asset.primaryPreviewFile','license'])->latest('updated_at')->get()->map(fn(DesignProject $p)=>[
            'uuid'=>$p->uuid,'title'=>$p->title,'status'=>$p->status,'updated_at'=>$p->updated_at?->diffForHumans(),
            'canvas'=>[$p->canvas_width,$p->canvas_height],
            'preview_url'=>$p->asset?->primaryPreviewFile ? route('assets.preview',[$p->asset,$p->asset->primaryPreviewFile]) : null,
            'edit_url'=>route('account.designs.edit',$p),
        ]);
        return Inertia::render('Account/Designs/Index',['projects'=>$projects]);
    }

    public function store(Request $request, License $license): RedirectResponse
    {
        abort_unless((int)$license->user_id===(int)$request->user()->id && $license->isActive() && $license->asset_id,403);
        $license->load('asset.primaryPreviewFile');
        $width=max(1,(int)($license->asset?->primaryPreviewFile?->width ?? 1920));
        $height=max(1,(int)($license->asset?->primaryPreviewFile?->height ?? 1080));
        $project=DesignProject::create([
            'user_id'=>$request->user()->id,'license_id'=>$license->id,'asset_id'=>$license->asset_id,
            'title'=>($license->asset?->title ?? 'Untitled design').' — Custom',
            'canvas_width'=>$width,'canvas_height'=>$height,
            'design_json'=>['version'=>1,'objects'=>[]], 'last_opened_at'=>now(),
        ]);
        return redirect()->route('account.designs.edit',$project);
    }

    public function edit(Request $request, DesignProject $design): Response
    {
        $this->owned($request,$design);
        $design->load(['asset.primaryPreviewFile','license','uploads']);
        $design->forceFill(['last_opened_at'=>now()])->save();
        $file=$design->asset?->primaryPreviewFile;
        return Inertia::render('Account/Designs/Edit',[
            'project'=>[
                'uuid'=>$design->uuid,'title'=>$design->title,'canvas_width'=>$design->canvas_width,'canvas_height'=>$design->canvas_height,
                'design_json'=>$design->design_json ?: ['version'=>1,'objects'=>[]],
                'source_url'=>$file ? route('assets.preview',[$design->asset,$file]) : null,
                'save_url'=>route('account.designs.update',$design),'upload_url'=>route('account.designs.uploads.store',$design),
                'uploads'=>$design->uploads->map(fn($u)=>['uuid'=>$u->uuid,'name'=>$u->original_filename,'url'=>route('account.designs.uploads.show',[$design,$u->uuid])]),
            ],
            'export_presets'=>[['name'=>'Social Square','width'=>1080,'height'=>1080],['name'=>'Social Portrait','width'=>1080,'height'=>1350],['name'=>'Story / Reel','width'=>1080,'height'=>1920],['name'=>'HD Landscape','width'=>1920,'height'=>1080]],
        ]);
    }

    public function update(Request $request, DesignProject $design): RedirectResponse
    {
        $this->owned($request,$design);
        $data=$request->validate([
            'title'=>['required','string','max:120'],
            'canvas_width'=>['required','integer','min:320','max:12000'],
            'canvas_height'=>['required','integer','min:320','max:12000'],
            'design_json' => ['required', 'array'],
            'design_json.version' => ['required', 'integer'],
            'design_json.fabric' => ['required_if:design_json.version,2', 'array'],
            'design_json.fabric.objects' => ['required_if:design_json.version,2', 'array', 'max:200'],
            'design_json.objects' => ['required_if:design_json.version,1', 'array', 'max:200'],
        ]);
        $design->update($data);
        return back()->with('success','Design saved.');
    }

    public function destroy(Request $request, DesignProject $design): RedirectResponse
    {
        $this->owned($request,$design);
        foreach($design->uploads as $upload) Storage::disk($upload->disk)->delete($upload->path);
        $design->delete();
        return redirect()->route('account.designs.index')->with('success','Design deleted.');
    }

    private function owned(Request $request, DesignProject $design): void { abort_unless((int)$design->user_id===(int)$request->user()->id,403); }
}
