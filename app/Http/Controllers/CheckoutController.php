<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\LicenseType;
use App\Services\StripeCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(
        protected StripeCheckoutService $stripeCheckoutService
    ) {
    }

    public function start(
        Request $request,
        Image $image
    ): RedirectResponse {
        $validated = $request->validate([
            'license_type_id' => ['required', 'exists:license_types,id'],
        ]);

        $licenseType = LicenseType::query()
            ->where('is_active', true)
            ->findOrFail($validated['license_type_id']);

        $session = $this->stripeCheckoutService->createCheckoutSession(
            $request->user(),
            $image,
            $licenseType
        );

        return redirect()->away($session->url);
    }

    public function success(Request $request): Response
    {
        return Inertia::render('Checkout/Success', [
            'sessionId' => $request->string('session_id')->toString(),
        ]);
    }

    public function cancel(): Response
    {
        return Inertia::render('Checkout/Cancel');
    }
}