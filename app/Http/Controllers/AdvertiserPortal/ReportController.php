<?php

namespace App\Http\Controllers\AdvertiserPortal;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\AdvertisingCampaign;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends PortalController
{
    public function index(Request $request)
    {
        $advertiser=$this->advertiser($request);$campaigns=$advertiser->campaigns()->get();
        $rows=$campaigns->map(fn($campaign)=>$this->row($campaign));
        return Inertia::render('Advertiser/Reports/Index',['advertiser'=>$advertiser,'membership'=>$this->membership($request),'campaigns'=>$rows,'summary'=>['impressions'=>$rows->sum('impressions'),'clicks'=>$rows->sum('clicks'),'ctr_percent'=>$rows->sum('impressions')?round(($rows->sum('clicks')/$rows->sum('impressions'))*100,2):0]]);
    }
    private function row(AdvertisingCampaign $campaign): array
    {
        $events=AnalyticsEvent::query()->where('subject_type',$campaign->getMorphClass())->where('subject_id',$campaign->id)->whereIn('event_name',[AnalyticsEventName::AdvertisingImpression,AnalyticsEventName::AdvertisingClicked])->selectRaw('event_name, COUNT(*) aggregate')->groupBy('event_name')->get()->mapWithKeys(fn($r)=>[($r->event_name instanceof \BackedEnum?$r->event_name->value:$r->event_name)=>(int)$r->aggregate]);
        $impressions=$events->get(AnalyticsEventName::AdvertisingImpression->value,0);$clicks=$events->get(AnalyticsEventName::AdvertisingClicked->value,0);
        return ['id'=>$campaign->id,'name'=>$campaign->name,'status'=>$campaign->status,'impressions'=>$impressions,'clicks'=>$clicks,'ctr_percent'=>$impressions?round(($clicks/$impressions)*100,2):0,'impression_goal'=>$campaign->impression_goal,'click_goal'=>$campaign->click_goal];
    }
}
