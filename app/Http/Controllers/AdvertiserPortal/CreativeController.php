<?php

namespace App\Http\Controllers\AdvertiserPortal;

use App\Models\AdCreative;
use App\Models\AdvertisingCampaign;
use App\Services\AdCreativeMediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CreativeController extends PortalController
{
    public function index(Request $request, AdvertisingCampaign $campaign)
    {
        $this->guardCampaign($request, $campaign);
        return Inertia::render('Advertiser/Creatives/Index', [
            'advertiser' => $this->advertiser($request), 'membership' => $this->membership($request),
            'campaign' => $campaign->load('placements'),
            'creatives' => $campaign->creatives()->with('placement')->latest()->get(),
        ]);
    }

    public function create(Request $request, AdvertisingCampaign $campaign)
    {
        $this->guardCampaign($request, $campaign); $this->guardCreativeAccess($request);
        return Inertia::render('Advertiser/Creatives/Form', ['advertiser'=>$this->advertiser($request),'membership'=>$this->membership($request),'campaign'=>$campaign->load('placements'),'creative'=>null]);
    }

    public function store(Request $request, AdvertisingCampaign $campaign, AdCreativeMediaService $media)
    {
        $this->guardCampaign($request, $campaign); $this->guardCreativeAccess($request);
        $data = $this->validated($request, $campaign, false);
        $data['uuid']=(string)Str::uuid(); $data['advertising_campaign_id']=$campaign->id; $data['status']='draft';
        $this->attachMedia($request,$data,$media,'advertising/'.$campaign->uuid.'/creatives/'.Str::uuid());
        $campaign->creatives()->create($data);
        return to_route('advertiser.campaigns.creatives.index',$campaign)->with('success','Creative added.');
    }

    public function edit(Request $request, AdvertisingCampaign $campaign, AdCreative $creative)
    {
        $this->guardCreative($request,$campaign,$creative); $this->guardCreativeAccess($request);
        abort_if($creative->status === 'approved', 422, 'Approved creatives cannot be edited.');
        return Inertia::render('Advertiser/Creatives/Form',['advertiser'=>$this->advertiser($request),'membership'=>$this->membership($request),'campaign'=>$campaign->load('placements'),'creative'=>$creative->load('placement')]);
    }

    public function update(Request $request, AdvertisingCampaign $campaign, AdCreative $creative, AdCreativeMediaService $media)
    {
        $this->guardCreative($request,$campaign,$creative); $this->guardCreativeAccess($request);
        abort_if($creative->status === 'approved', 422, 'Approved creatives cannot be edited.');
        $data=$this->validated($request,$campaign,true);
        if($request->hasFile('media')||$request->hasFile('media_original')){$media->deleteCreativeMedia($creative);$this->attachMedia($request,$data,$media,'advertising/'.$campaign->uuid.'/creatives/'.$creative->uuid);}
        if($creative->status==='rejected')$data['status']='draft';
        $creative->update($data);
        return to_route('advertiser.campaigns.creatives.index',$campaign)->with('success','Creative updated.');
    }

    public function submit(Request $request, AdvertisingCampaign $campaign, AdCreative $creative)
    {
        $this->guardCreative($request,$campaign,$creative); $this->guardCreativeAccess($request);
        abort_unless(in_array($creative->status,['draft','rejected'],true)&&$creative->media_path,422);
        $creative->update(['status'=>'submitted','submitted_at'=>now(),'rejection_reason'=>null]);
        return back()->with('success','Creative submitted for approval.');
    }

    private function validated(Request $request, AdvertisingCampaign $campaign, bool $editing): array
    {
        $image=$request->input('creative_type','image')==='image';
        return $request->validate([
            'ad_placement_id'=>['nullable',Rule::exists('ad_placements','id')->where(fn($q)=>$q->whereIn('id',$campaign->placements()->pluck('ad_placements.id')))],
            'name'=>'required|string|max:255','creative_type'=>'required|in:image,video',
            'media_original'=>[$editing?'nullable':($image?'required':'nullable'),'file','image','mimes:jpeg,jpg,png,webp','max:20480'],
            'media'=>[$editing?'nullable':'required','file',Rule::when($image,['image','mimes:jpeg,jpg,png,webp','max:20480'],['mimes:mp4,webm','max:102400'])],
            'media_edit_data'=>'nullable|json','width'=>'nullable|integer|min:1|max:10000','height'=>'nullable|integer|min:1|max:10000',
            'headline'=>'nullable|string|max:255','body'=>'nullable|string|max:2000','cta_label'=>'nullable|string|max:100','destination_url'=>'nullable|url|max:1000','alt_text'=>'nullable|string|max:1000',
        ]);
    }

    private function attachMedia(Request $request,array &$data,AdCreativeMediaService $media,string $directory): void
    {
        if($data['creative_type']==='image'){$paths=$media->storeImage($request->file('media_original'),$request->file('media'),$directory);$data=array_merge($data,$paths,['mime_type'=>$request->file('media')->getMimeType(),'file_size'=>$request->file('media')->getSize(),'original_filename'=>$request->file('media_original')->getClientOriginalName(),'media_edit_data'=>json_decode($data['media_edit_data']??'null',true)]);}else{$paths=$media->storeVideo($request->file('media'),$directory);$data=array_merge($data,$paths,['mime_type'=>$request->file('media')->getMimeType(),'file_size'=>$request->file('media')->getSize(),'original_filename'=>$request->file('media')->getClientOriginalName(),'media_edit_data'=>null]);}
    }

    private function guardCampaign(Request $request, AdvertisingCampaign $campaign): void { abort_unless($campaign->advertiser_id===$this->advertiser($request)->id,404); }
    private function guardCreative(Request $request,AdvertisingCampaign $campaign,AdCreative $creative): void{$this->guardCampaign($request,$campaign);abort_unless($creative->advertising_campaign_id===$campaign->id,404);}
    private function guardCreativeAccess(Request $request): void { abort_unless($this->membership($request)->canManageCreatives(),403); }
}
