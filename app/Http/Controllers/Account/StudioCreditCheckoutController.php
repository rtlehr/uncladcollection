<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\DesignProject;
use App\Models\StudioCreditPackage;
use App\Services\DesignStudio\StudioCheckoutService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class StudioCreditCheckoutController extends Controller
{
    public function __invoke(Request $request, DesignProject $design, StudioCreditPackage $package, StudioCheckoutService $checkout): Response
    {
        abort_unless((int) $design->user_id === (int) $request->user()->id, 403);

        $session = $checkout->create($request->user(), $design, $package);

        return Inertia::location($session->url);
    }
}
