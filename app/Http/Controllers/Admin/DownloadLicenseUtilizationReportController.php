<?php

namespace App\Http\Controllers\Admin;

use App\Analytics\AnalyticsPeriod;
use App\Analytics\DownloadLicenseUtilizationService;
use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadLicenseUtilizationReportController extends Controller
{
    public function index(Request $request, DownloadLicenseUtilizationService $reports): Response { $filters=$this->validated($request);$period=AnalyticsPeriod::fromRequest($request);return Inertia::render('Admin/Analytics/Downloads/Index',['filters'=>array_merge(['period'=>$request->input('period','30_days'),'search'=>'','status'=>''],$period->toArray(),$filters),'report'=>$reports->report($period,$filters)]); }
    public function show(Request $request, License $license, DownloadLicenseUtilizationService $reports): Response { $period=AnalyticsPeriod::fromRequest($request);return Inertia::render('Admin/Analytics/Downloads/Show',['filters'=>array_merge(['period'=>$request->input('period','30_days')],$period->toArray()),'report'=>$reports->detail($license,$period)]); }
    public function export(Request $request, DownloadLicenseUtilizationService $reports): StreamedResponse { $period=AnalyticsPeriod::fromRequest($request);$rows=$reports->exportRows($period,$this->validated($request));return response()->streamDownload(function()use($rows){$o=fopen('php://output','w');fputcsv($o,['License ID','License key','Customer','License','Asset','Status','Downloads used','Download limit','Period downloads','Utilization percent','Expires at','Order value cents']);foreach($rows as $r)fputcsv($o,$r);fclose($o);},'download-license-utilization-'.$period->start->toDateString().'-'.$period->end->toDateString().'.csv',['Content-Type'=>'text/csv']); }
    private function validated(Request $request): array { return $request->validate(['period'=>['nullable','in:7_days,30_days,90_days,year_to_date,custom'],'start_date'=>['nullable','date'],'end_date'=>['nullable','date'],'search'=>['nullable','string','max:120'],'status'=>['nullable','in:,active,expired,revoked,refunded']]); }
}
