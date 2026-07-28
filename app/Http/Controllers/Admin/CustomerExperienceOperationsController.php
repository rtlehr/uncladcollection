<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminActivityService;
use App\Services\CustomerExperienceMaintenanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerExperienceOperationsController extends Controller
{
    public function index(CustomerExperienceMaintenanceService $maintenance): Response
    {
        return Inertia::render('Admin/CustomerExperience/Index', ['health' => $maintenance->health()]);
    }

    public function maintain(Request $request, CustomerExperienceMaintenanceService $maintenance, AdminActivityService $activity): RedirectResponse
    {
        $validated = $request->validate(['dry_run' => ['required', 'boolean']]);
        $result = $maintenance->maintain((bool) $validated['dry_run']);
        $activity->log('customer_experience_maintenance', description: json_encode($result));

        $verb = $result['dry_run'] ? 'would remove' : 'removed';
        return back()->with('success', "Maintenance {$verb} {$result['watch_events']} notification watch events and {$result['download_packages']} temporary packages.");
    }
}
