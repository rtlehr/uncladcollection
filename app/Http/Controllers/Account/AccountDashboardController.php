<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Account\AccountDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountDashboardController extends Controller
{
    public function __invoke(Request $request, AccountDashboardService $dashboard): Response
    {
        return Inertia::render('Account/Index', $dashboard->forUser($request->user()));
    }
}
