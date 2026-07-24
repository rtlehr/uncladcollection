<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\Support\SupportReportingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SupportReportController extends Controller
{
    public function __invoke(Request $request, SupportReportingService $reports): Response
    {
        $this->authorize('viewAny', SupportTicket::class);
        abort_unless($request->user()->hasPermission('view_support_reports'), 403);

        $data = $request->validate([
            'period' => ['nullable', Rule::in(['7', '30', '90', '365'])],
        ]);

        $days = (int) ($data['period'] ?? 30);
        $to = now()->endOfDay();
        $from = now()->subDays($days - 1)->startOfDay();

        return Inertia::render('Admin/Support/Reports', [
            'report' => $reports->report($from, $to),
            'filters' => ['period' => (string) $days],
        ]);
    }
}
