<?php
namespace App\Http\Controllers\Account;
use App\Http\Controllers\Controller;
use App\Models\DesignProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
class DesignUploadController extends Controller {
 public function store(Request $request, DesignProject $design): JsonResponse {
  abort_unless((int)$design->user_id===(int)$request->user()->id,403);
  $data=$request->validate(['image'=>['required','image','mimes:jpg,jpeg,png,webp','max:10240','dimensions:max_width=8000,max_height=8000']]);
  $file=$data['image']; $size=@getimagesize($file->getRealPath()) ?: [null,null];
  $path=$file->store("designs/{$request->user()->id}/{$design->uuid}",'local');
  $upload=$design->uploads()->create(['user_id'=>$request->user()->id,'disk'=>'local','path'=>$path,'original_filename'=>$file->getClientOriginalName(),'mime_type'=>$file->getMimeType(),'size_bytes'=>$file->getSize(),'width'=>$size[0],'height'=>$size[1]]);
  return response()->json(['uuid'=>$upload->uuid,'name'=>$upload->original_filename,'url'=>route('account.designs.uploads.show',[$design,$upload->uuid])],201);
 }
 public function show(Request $request, DesignProject $design, string $upload): StreamedResponse {
  abort_unless((int)$design->user_id===(int)$request->user()->id,403); $record=$design->uploads()->where('uuid',$upload)->firstOrFail();
  return Storage::disk($record->disk)->response($record->path,$record->original_filename,['Content-Type'=>$record->mime_type,'Cache-Control'=>'private, max-age=3600']);
 }
}
