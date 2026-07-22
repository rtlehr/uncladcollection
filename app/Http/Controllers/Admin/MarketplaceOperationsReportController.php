<?php

namespace App\Http\Controllers\Admin;

use App\Analytics\AnalyticsPeriod;
use App\Analytics\MarketplaceOperationsService;
use App\Enums\OrderFulfillmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketplaceOperationsReportController extends Controller
{
    public function index(Request $request, MarketplaceOperationsService $reports): Response
    {
        $filters = $this->validated($request);
        $period = AnalyticsPeriod::fromRequest($request);
        return Inertia::render('Admin/Analytics/Operations/Index', [
            'filters' => array_merge(['period' => $request->input('period', '30_days'), 'search' => '', 'status' => '', 'fulfillment_status' => ''], $period->toArray(), $filters),
            'report' => $reports->report($period, $filters),
            'fulfillmentStatuses' => collect(OrderFulfillmentStatus::cases())->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()])->all(),
        ]);
    }

    public function show(Request $request, Order $order, MarketplaceOperationsService $reports): Response
    {
        $period = AnalyticsPeriod::fromRequest($request);
        return Inertia::render('Admin/Analytics/Operations/Show', [
            'filters' => array_merge(['period' => $request->input('period', '30_days')], $period->toArray()),
            'report' => $reports->detail($order, $period),
        ]);
    }

    public function export(Request $request, MarketplaceOperationsService $reports): StreamedResponse
    {
        $period = AnalyticsPeriod::fromRequest($request);
        $rows = $reports->exportRows($period, $this->validated($request));
        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Order ID', 'Order number', 'Customer', 'Payment status', 'Fulfillment status', 'Provider', 'Total cents', 'Paid at', 'Fulfilled at', 'Fulfillment hours', 'Needs attention']);
            foreach ($rows as $row) fputcsv($output, $row);
            fclose($output);
        }, 'marketplace-operations-'.$period->start->toDateString().'-'.$period->end->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'period' => ['nullable', 'in:7_days,30_days,90_days,year_to_date,custom'],
            'start_date' => ['nullable', 'date'], 'end_date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:,pending,paid,failed,canceled,refunded,partially_refunded'],
            'fulfillment_status' => ['nullable', 'in:,new,processing,ready_to_package,packaged,shipped,delivered,fulfilled,canceled'],
        ]);
    }
}
