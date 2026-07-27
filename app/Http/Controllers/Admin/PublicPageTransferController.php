<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\PublicPage; use App\Services\PublicPages\PublicPageTransferService; use Illuminate\Http\RedirectResponse; use Illuminate\Http\Request; use Illuminate\Http\Response; use InvalidArgumentException;
class PublicPageTransferController extends Controller
{
 public function export(PublicPageTransferService $t):Response{$this->authorize('viewAny',PublicPage::class);return response($t->exportJson(),200,['Content-Type'=>'application/json; charset=UTF-8','Content-Disposition'=>'attachment; filename="public-pages-'.now()->format('Y-m-d-His').'.json"','Cache-Control'=>'no-store, private']);}
 public function import(Request $r,PublicPageTransferService $t):RedirectResponse{$this->authorize('create',PublicPage::class);$v=$r->validate(['file'=>['required','file','mimes:json,txt','max:10240'],'mode'=>['required','in:merge,replace'],'confirm_replace'=>['nullable','accepted_if:mode,replace']]);try{$s=$t->importJson(file_get_contents($v['file']->getRealPath()),$v['mode']);}catch(InvalidArgumentException $e){return back()->withErrors(['file'=>$e->getMessage()]);}return back()->with('success',sprintf('Public Pages import complete: %d created, %d updated, %d unchanged, %d removed.',$s['created'],$s['updated'],$s['unchanged'],$s['deleted']));}
}
