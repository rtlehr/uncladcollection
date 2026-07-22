<?php

namespace App\Http\Controllers\Admin;

use App\Analytics\AnalyticsPeriod;
use App\Analytics\CustomerConversionService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerConversionReportController extends Controller
{
    public function index(Request $request, CustomerConversionService $reports): Response
    {
        $filters = $this->validated($request); $period = AnalyticsPeriod::fromRequest($request);
        return Inertia::render('Admin/Analytics/Customers/Index', ['filters'=>array_merge(['period'=>$request->input('period','30_days'),'search'=>'','segment'=>'all'],$period->toArray(),$filters),'report'=>$reports->report($period,$filters)]);
    }

    public function show(Request $request, User $user, CustomerConversionService $reports): Response
    {
        $this->validated($request, false); $period = AnalyticsPeriod::fromRequest($request);
        return Inertia::render('Admin/Analytics/Customers/Show', ['filters'=>array_merge(['period'=>$request->input('period','30_days')],$period->toArray()),'report'=>$reports->detail($user,$period)]);
    }

    public function export(Request $request, CustomerConversionService $reports): StreamedResponse
    {
        $filters=$this->validated($request); $period=AnalyticsPeriod::fromRequest($request); $filename='customer-conversion-'.$period->start->toDateString().'-'.$period->end->toDateString().'.csv';
        return response()->streamDownload(function() use($reports,$period,$filters):void{$o=fopen('php://output','wb');fputcsv($o,['Customer ID','Name','Email','Segment','Period orders','Period revenue cents','Lifetime orders','Lifetime revenue cents','Downloads','Last purchase at']);foreach($reports->exportRows($period,$filters) as $row)fputcsv($o,$row);fclose($o);},$filename,['Content-Type'=>'text/csv; charset=UTF-8']);
    }

    private function validated(Request $request, bool $customerFilters=true): array
    {
        $rules=['period'=>['nullable','in:7_days,30_days,90_days,year_to_date,custom'],'start_date'=>['required_if:period,custom','nullable','date'],'end_date'=>['required_if:period,custom','nullable','date','after_or_equal:start_date']];
        if($customerFilters)$rules += ['search'=>['nullable','string','max:120'],'segment'=>['nullable','in:all,first_time,repeat']];
        return $request->validate($rules);
    }
}
