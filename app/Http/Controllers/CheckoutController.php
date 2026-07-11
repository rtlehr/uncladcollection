<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\LicenseType;
use App\Services\PurchaseService;
use App\Services\StripeCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(
        protected StripeCheckoutService $stripeCheckoutService,
        protected PurchaseService $purchaseService,
    ) {
    }

    public function start(
        Request $request,
        Image $image,
    ): RedirectResponse {
        abort_unless(
            $image->is_active,
            404,
            'This image is not available for purchase.',
        );

        $validated = $request->validate([
            'license_type_id' => [
                'required',
                'integer',
                'exists:license_types,id',
            ],
        ]);

        $licenseType = LicenseType::query()
            ->where('is_active', true)
            ->findOrFail($validated['license_type_id']);

        if (
            $this->purchaseService->userHasPurchasedImage(
                $request->user(),
                $image,
            )
        ) {
            throw ValidationException::withMessages([
                'image' => 'You already have an active license for this image.',
            ]);
        }

        $session = $this->stripeCheckoutService->createCheckoutSession(
            $request->user(),
            $image,
            $licenseType,
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
