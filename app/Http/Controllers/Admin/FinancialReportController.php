<?php

namespace App\Http\Controllers\Admin;

use App\Analytics\AnalyticsPeriod;
use App\Analytics\FinancialReportingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialReportController extends Controller
{
    public function index(Request $request, FinancialReportingService $reports): Response
    {
        $this->validatePeriod($request);
        $period = AnalyticsPeriod::fromRequest($request);

        return Inertia::render('Admin/Analytics/Financial', [
            'filters' => array_merge(['period' => $request->input('period', '30_days')], $period->toArray()),
            'report' => $reports->report($period),
        ]);
    }

    public function export(Request $request, FinancialReportingService $reports): StreamedResponse
    {
        $this->validatePeriod($request);
        $period = AnalyticsPeriod::fromRequest($request);
        $filename = 'financial-report-'.$period->start->toDateString().'-'.$period->end->toDateString().'.csv';

        return response()->streamDownload(function () use ($reports, $period): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['Order number', 'Paid at', 'Customer', 'Provider', 'Subtotal cents', 'Discount cents', 'Tax cents', 'Collected cents', 'Refunded cents', 'Net cents']);
            foreach ($reports->exportRows($period) as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validatePeriod(Request $request): void
    {
        $request->validate([
            'period' => ['nullable', 'in:7_days,30_days,90_days,year_to_date,custom'],
            'start_date' => ['required_if:period,custom', 'nullable', 'date'],
            'end_date' => ['required_if:period,custom', 'nullable', 'date', 'after_or_equal:start_date'],
        ]);
    }
}
