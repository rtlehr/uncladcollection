<?php

namespace App\Analytics;

use App\Models\Download;
use App\Models\License;
use Illuminate\Support\Collection;

class DownloadLicenseUtilizationService
{
    public function report(AnalyticsPeriod $period, array $filters = []): array
    {
        $rows = $this->licenseRows($period, $filters);
        $downloads = Download::query()->whereBetween('downloaded_at', [$period->start, $period->end])->get();

        return [
            'summary' => [
                'licenses_measured' => $rows->count(),
                'downloads' => (int) $rows->sum('period_downloads'),
                'unique_downloaders' => $downloads->pluck('user_id')->filter()->unique()->count(),
                'unused_licenses' => $rows->where('downloads_used', 0)->count(),
                'near_limit_licenses' => $rows->where('near_limit', true)->count(),
                'average_utilization_percent' => round((float) $rows->avg('utilization_percent'), 1),
            ],
            'licenses' => $rows->values()->all(),
            'statuses' => $this->group($rows, 'status'),
            'license_types' => $this->group($rows, 'license_name'),
            'formats' => $this->formatDemand($rows),
            'opportunities' => [
                'unused' => $rows->where('downloads_used', 0)->take(10)->values()->all(),
                'near_limit' => $rows->where('near_limit', true)->take(10)->values()->all(),
                'high_value_low_use' => $rows->filter(fn ($r) => $r['order_value_cents'] >= 2000 && $r['utilization_percent'] < 25)->take(10)->values()->all(),
            ],
        ];
    }

    public function detail(License $license, AnalyticsPeriod $period): array
    {
        $row = $this->licenseRows($period, ['license_id' => $license->id])->first();
        $daily = Download::query()->where('license_id', $license->id)->whereBetween('downloaded_at', [$period->start, $period->end])
            ->selectRaw('DATE(downloaded_at) metric_date, COUNT(*) downloads')->groupByRaw('DATE(downloaded_at)')->pluck('downloads', 'metric_date');
        $timeline = [];
        for ($date = $period->start->startOfDay(); $date->lte($period->end); $date = $date->addDay()) {
            $key = $date->toDateString();
            $timeline[] = ['date'=>$key,'label'=>$date->format('M j'),'downloads'=>(int)($daily[$key]??0),'orders'=>0,'revenue_cents'=>0];
        }
        return [
            'license' => ['id'=>$license->id,'license_key'=>$license->license_key,'status'=>$license->status,'license_name'=>$license->license_name,'starts_at'=>$license->starts_at?->toIso8601String(),'expires_at'=>$license->expires_at?->toIso8601String()],
            'performance' => $row,
            'timeline' => $timeline,
            'downloads' => $license->downloads()->latest('downloaded_at')->limit(50)->get()->map(fn($d)=>['id'=>$d->id,'download_type'=>$d->download_type,'downloaded_at'=>$d->downloaded_at?->toIso8601String(),'ip_address'=>$d->ip_address])->all(),
            'included_files' => collect($license->included_asset_files_snapshot ?? [])->map(fn($f)=>['name'=>$f['original_filename']??$f['stored_filename']??$f['name']??'File','extension'=>strtolower($f['extension']??pathinfo($f['original_filename']??'', PATHINFO_EXTENSION)?:'unknown')])->values()->all(),
        ];
    }

    public function exportRows(AnalyticsPeriod $period, array $filters=[]): array
    {
        return $this->licenseRows($period,$filters)->map(fn($r)=>[$r['license_id'],$r['license_key'],$r['customer_email'],$r['license_name'],$r['asset_title'],$r['status'],$r['downloads_used'],$r['download_limit'],$r['period_downloads'],$r['utilization_percent'],$r['expires_at'],$r['order_value_cents']])->all();
    }

    private function licenseRows(AnalyticsPeriod $period, array $filters=[]): Collection
    {
        $periodDownloads = Download::query()->whereBetween('downloaded_at',[$period->start,$period->end])->selectRaw('license_id, COUNT(*) aggregate')->groupBy('license_id')->pluck('aggregate','license_id');
        $query=License::query()->with(['user:id,name,email','asset:id,title,asset_type','image:id,title','licenseType:id,name','orderItem:id,total_price_cents,asset_title,image_title']);
        if(!empty($filters['license_id'])) $query->whereKey($filters['license_id']);
        if(!empty($filters['status'])) $query->where('status',$filters['status']);
        if(!empty($filters['search'])) $query->where(fn($q)=>$q->where('license_key','like','%'.$filters['search'].'%')->orWhere('license_name','like','%'.$filters['search'].'%')->orWhereHas('user',fn($u)=>$u->where('email','like','%'.$filters['search'].'%')));
        return $query->get()->map(function($l) use($periodDownloads){
            $limit=$l->download_limit; $used=(int)$l->downloads_used; $util=$limit?round(min(100,($used/$limit)*100),1):($used>0?100:0);
            $snapshot=collect($l->included_asset_files_snapshot??[]); $formats=$snapshot->map(fn($f)=>strtolower($f['extension']??pathinfo($f['original_filename']??'',PATHINFO_EXTENSION)?:'unknown'))->filter()->unique()->values()->all();
            return ['license_id'=>$l->id,'license_key'=>$l->license_key,'customer_name'=>$l->user?->name,'customer_email'=>$l->user?->email,'license_name'=>$l->license_name?:$l->licenseType?->name?:'Unspecified','asset_title'=>$l->asset?->title?:$l->image?->title?:$l->orderItem?->asset_title?:$l->orderItem?->image_title?:'Unknown asset','asset_type'=>$l->asset?->asset_type?->value??(string)($l->asset?->asset_type??'image'),'status'=>$l->status,'downloads_used'=>$used,'download_limit'=>$limit,'period_downloads'=>(int)($periodDownloads[$l->id]??0),'utilization_percent'=>$util,'near_limit'=>$limit!==null&&$limit>0&&$used>=$limit-1,'starts_at'=>$l->starts_at?->toIso8601String(),'expires_at'=>$l->expires_at?->toIso8601String(),'order_value_cents'=>(int)($l->orderItem?->total_price_cents??0),'formats'=>$formats];
        })->sortByDesc(fn($r)=>[$r['period_downloads'],$r['utilization_percent']])->values();
    }

    private function group(Collection $rows,string $key): array { return $rows->groupBy($key)->map(fn($g,$label)=>['label'=>(string)$label,'licenses'=>$g->count(),'downloads'=>(int)$g->sum('period_downloads'),'average_utilization_percent'=>round((float)$g->avg('utilization_percent'),1)])->sortByDesc('downloads')->values()->all(); }
    private function formatDemand(Collection $rows): array { $out=[]; foreach($rows as $r) foreach($r['formats'] as $f){$out[$f]??=['label'=>strtoupper($f),'licenses'=>0,'downloads'=>0];$out[$f]['licenses']++;$out[$f]['downloads']+=$r['period_downloads'];} return collect($out)->sortByDesc('downloads')->values()->all(); }
}
