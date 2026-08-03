<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AiPromptExample;
use App\Services\Ai\ContentStudio\PromptExampleImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class AiPromptExampleController extends Controller
{
    public function index(Request $request): Response
    {
        $search=trim((string)$request->input('search','')); $context=$request->input('context');
        $items=AiPromptExample::query()->when($search!=='',fn($q)=>$q->where(fn($q)=>$q->where('title','like',"%{$search}%")->orWhere('content','like',"%{$search}%")))->when($context,fn($q)=>$q->where('content_context',$context))->orderBy('title')->paginate(30)->withQueryString();
        return Inertia::render('Admin/AiContent/PromptExamples/Index',['items'=>$items,'filters'=>['search'=>$search,'context'=>$context]]);
    }
    public function store(Request $request): RedirectResponse
    {
        $v=$request->validate(['title'=>'required|string|max:150','content'=>'required|string|max:10000','category'=>'nullable|string|max:100','content_context'=>'required|in:general,adult_naturism,family_naturism','intended_uses'=>'array','subject_tags'=>'array','is_family_friendly'=>'boolean','is_enabled'=>'boolean']);
        AiPromptExample::create($v+['created_by'=>$request->user()?->id]); return back()->with('success','Prompt example created.');
    }
    public function update(Request $request,AiPromptExample $aiPromptExample): RedirectResponse
    {
        $v=$request->validate(['title'=>'required|string|max:150','content'=>'required|string|max:10000','category'=>'nullable|string|max:100','content_context'=>'required|in:general,adult_naturism,family_naturism','intended_uses'=>'array','subject_tags'=>'array','is_family_friendly'=>'boolean','is_enabled'=>'boolean']);
        $aiPromptExample->update($v); return back()->with('success','Prompt example updated.');
    }
    public function destroy(AiPromptExample $aiPromptExample): RedirectResponse { $aiPromptExample->delete(); return back()->with('success','Prompt example deleted.'); }
    public function import(Request $request,PromptExampleImporter $importer): RedirectResponse
    {
        $request->validate(['file'=>'required|file|mimes:json,txt|max:5120']); $stats=$importer->import($request->file('file')->getRealPath(),$request->user()?->id);
        return back()->with('success',"Imported {$stats['created']} prompt(s); skipped {$stats['duplicates']} duplicate(s) and {$stats['invalid']} invalid record(s).");
    }
}
