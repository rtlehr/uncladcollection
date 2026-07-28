<?php

namespace App\Http\Controllers\Admin;

use App\Analytics\AnalyticsPeriod;
use App\Analytics\CustomerRetentionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerRetentionReportController extends Controller
{
    public function __invoke(Request $request, CustomerRetentionService $service): Response
    {
        $request->validate([
            'period' => ['nullable', 'in:7_days,30_days,90_days,year_to_date,custom'],
            'start_date' => ['required_if:period,custom', 'nullable', 'date'],
            'end_date' => ['required_if:period,custom', 'nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $period = AnalyticsPeriod::fromRequest($request);

        return Inertia::render('Admin/Analytics/Retention', [
            'filters' => array_merge(['period' => $request->input('period', '30_days')], $period->toArray()),
            'report' => $service->report($period),
        ]);
    }
}
