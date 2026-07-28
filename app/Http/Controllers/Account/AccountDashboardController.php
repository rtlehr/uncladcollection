<?php

namespace App\Http\Controllers\Account;

use App\Analytics\AnalyticsTracker;
use App\Enums\AnalyticsEventName;
use App\Http\Controllers\Controller;
use App\Services\Account\AccountDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountDashboardController extends Controller
{
    public function __invoke(Request $request, AccountDashboardService $dashboard, AnalyticsTracker $analytics): Response
    {
        $analytics->record(AnalyticsEventName::AccountDashboardViewed, user: $request->user(), source: 'account');

        return Inertia::render('Account/Index', $dashboard->forUser($request->user()));
    }
}
