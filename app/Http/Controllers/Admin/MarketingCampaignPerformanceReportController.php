<?php

namespace App\Http\Controllers\Admin;

use App\Analytics\AnalyticsPeriod;
use App\Analytics\MarketingCampaignPerformanceService;
use App\Http\Controllers\Controller;
use App\Models\MarketingCampaign;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketingCampaignPerformanceReportController extends Controller
{
    public function index(Request $request, MarketingCampaignPerformanceService $reports): Response
    {
        $filters = $this->validated($request);
        $period = AnalyticsPeriod::fromRequest($request);

        return Inertia::render('Admin/Analytics/Campaigns/Index', [
            'filters' => array_merge(['period' => $request->input('period', '30_days'), 'search' => '', 'media_type' => '', 'status' => ''], $period->toArray(), $filters),
            'report' => $reports->report($period, $filters),
        ]);
    }

    public function show(Request $request, MarketingCampaign $marketingCampaign, MarketingCampaignPerformanceService $reports): Response
    {
        $period = AnalyticsPeriod::fromRequest($request);
        return Inertia::render('Admin/Analytics/Campaigns/Show', [
            'filters' => array_merge(['period' => $request->input('period', '30_days')], $period->toArray()),
            'report' => $reports->detail($marketingCampaign, $period),
        ]);
    }

    public function export(Request $request, MarketingCampaignPerformanceService $reports): StreamedResponse
    {
        $period = AnalyticsPeriod::fromRequest($request);
        $rows = $reports->exportRows($period, $this->validated($request));
        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Campaign ID', 'Campaign', 'Media type', 'Status', 'Impressions', 'Unique viewers', 'Clicks', 'Primary clicks', 'Secondary clicks', 'CTR percent', 'Influenced buyers', 'Influenced orders', 'Influenced revenue cents', 'Revenue per viewer cents']);
            foreach ($rows as $row) fputcsv($out, $row);
            fclose($out);
        }, 'marketing-campaign-performance-'.$period->start->toDateString().'-'.$period->end->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'period' => ['nullable', 'in:7_days,30_days,90_days,year_to_date,custom'],
            'start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'], 'media_type' => ['nullable', 'in:image,video'], 'status' => ['nullable', 'in:active,inactive'],
        ]);
    }
}
