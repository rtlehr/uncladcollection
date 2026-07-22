<?php

namespace App\Http\Controllers\Admin;

use App\Analytics\AnalyticsPeriod;
use App\Analytics\MarketplaceMetricsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsDashboardController extends Controller
{
    public function __invoke(Request $request, MarketplaceMetricsService $metrics): Response
    {
        $period = AnalyticsPeriod::fromRequest($request);

        return Inertia::render('Admin/Analytics/Index', [
            'filters' => array_merge(['period' => $request->input('period', '30_days')], $period->toArray()),
            'metrics' => $metrics->summary($period),
            'revenueTrend' => $metrics->revenueTrend($period),
        ]);
    }
}
