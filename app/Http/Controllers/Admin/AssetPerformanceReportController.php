<?php

namespace App\Http\Controllers\Admin;

use App\Analytics\AnalyticsPeriod;
use App\Analytics\AssetPerformanceService;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetPerformanceReportController extends Controller
{
    public function index(Request $request, AssetPerformanceService $reports): Response
    {
        $filters = $this->validated($request);
        $period = AnalyticsPeriod::fromRequest($request);

        return Inertia::render('Admin/Analytics/Assets/Index', [
            'filters' => array_merge(['period' => $request->input('period', '30_days'), 'search' => '', 'asset_type' => 'all', 'collection_id' => null], $period->toArray(), $filters),
            'report' => $reports->report($period, $filters),
            'collections' => Collection::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, Asset $asset, AssetPerformanceService $reports): Response
    {
        $this->validated($request, false);
        $period = AnalyticsPeriod::fromRequest($request);
        $asset->load('collection:id,name');

        return Inertia::render('Admin/Analytics/Assets/Show', [
            'filters' => array_merge(['period' => $request->input('period', '30_days')], $period->toArray()),
            'report' => $reports->detail($asset, $period),
        ]);
    }

    public function export(Request $request, AssetPerformanceService $reports): StreamedResponse
    {
        $filters = $this->validated($request);
        $period = AnalyticsPeriod::fromRequest($request);
        $filename = 'asset-performance-'.$period->start->toDateString().'-'.$period->end->toDateString().'.csv';

        return response()->streamDownload(function () use ($reports, $period, $filters): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['Asset ID', 'Title', 'Media type', 'Collection', 'Views', 'Favorites', 'Cart additions', 'Units sold', 'Revenue cents', 'Downloads', 'View-to-cart %', 'View-to-purchase %', 'Revenue per view cents']);
            foreach ($reports->exportRows($period, $filters) as $row) fputcsv($output, $row);
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validated(Request $request, bool $includeAssetFilters = true): array
    {
        $rules = [
            'period' => ['nullable', 'in:7_days,30_days,90_days,year_to_date,custom'],
            'start_date' => ['required_if:period,custom', 'nullable', 'date'],
            'end_date' => ['required_if:period,custom', 'nullable', 'date', 'after_or_equal:start_date'],
        ];
        if ($includeAssetFilters) {
            $rules += ['search' => ['nullable', 'string', 'max:120'], 'asset_type' => ['nullable', 'in:all,image,video,audio,document,vector,archive,other'], 'collection_id' => ['nullable', 'integer', 'exists:collections,id']];
        }
        return $request->validate($rules);
    }
}
