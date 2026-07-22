<?php

namespace App\Http\Controllers\Admin;

use App\Analytics\AnalyticsPeriod;
use App\Analytics\SearchDiscoveryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SearchDiscoveryReportController extends Controller
{
    public function index(Request $request, SearchDiscoveryService $reports): Response
    {
        $filters = $this->validated($request); $period = AnalyticsPeriod::fromRequest($request);
        return Inertia::render('Admin/Analytics/Search/Index', ['filters' => array_merge(['period' => $request->input('period', '30_days'), 'search' => '', 'result_status' => ''], $period->toArray(), $filters), 'report' => $reports->report($period, $filters)]);
    }

    public function show(Request $request, string $term, SearchDiscoveryService $reports): Response
    {
        $period = AnalyticsPeriod::fromRequest($request);
        return Inertia::render('Admin/Analytics/Search/Show', ['filters' => array_merge(['period' => $request->input('period', '30_days')], $period->toArray()), 'report' => $reports->detail(urldecode($term), $period)]);
    }

    public function export(Request $request, SearchDiscoveryService $reports): StreamedResponse
    {
        $period = AnalyticsPeriod::fromRequest($request); $rows = $reports->exportRows($period, $this->validated($request));
        return response()->streamDownload(function () use ($rows) { $out = fopen('php://output', 'w'); fputcsv($out, ['Term','Searches','Unique searchers','Average results','Zero-result searches','Low-result searches','Registered searches','Anonymous searches','Influenced orders','Influenced revenue cents','Search-to-purchase percent']); foreach ($rows as $row) fputcsv($out, $row); fclose($out); }, 'search-discovery-performance-'.$period->start->toDateString().'-'.$period->end->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }

    private function validated(Request $request): array
    {
        return $request->validate(['period' => ['nullable','in:7_days,30_days,90_days,year_to_date,custom'], 'start_date' => ['nullable','date'], 'end_date' => ['nullable','date'], 'search' => ['nullable','string','max:120'], 'result_status' => ['nullable','in:,zero,low']]);
    }
}
