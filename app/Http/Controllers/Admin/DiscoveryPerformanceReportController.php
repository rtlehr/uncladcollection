<?php

namespace App\Http\Controllers\Admin;

use App\Analytics\AnalyticsPeriod;
use App\Analytics\DiscoveryPerformanceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DiscoveryPerformanceReportController extends Controller
{
    public function index(Request $request, DiscoveryPerformanceService $service): Response
    {
        $request->validate(['period' => ['nullable', 'in:7_days,30_days,90_days,year_to_date,custom'], 'start_date' => ['required_if:period,custom', 'nullable', 'date'], 'end_date' => ['required_if:period,custom', 'nullable', 'date', 'after_or_equal:start_date']]);
        $period = AnalyticsPeriod::fromRequest($request);
        return Inertia::render('Admin/Discovery/Performance', [
            'filters' => array_merge(['period' => $request->input('period', '30_days')], $period->toArray()),
            'report' => $service->report($period->start, $period->end),
        ]);
    }
}
