<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\LicenseType;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {
    }

    public function purchase(
        Request $request,
        Image $image
    ): RedirectResponse {
        $request->validate([
            'license_type_id' => ['required', 'exists:license_types,id'],
        ]);

        $licenseType = LicenseType::findOrFail(
            $request->license_type_id
        );

        $order = $this->purchaseService->createPendingOrder(
            $request->user(),
            $image,
            $licenseType
        );

        /*
         * Temporary testing:
         * Auto-pay the order.
         */

        $this->purchaseService->markOrderPaid($order);

        return back()->with(
            'success',
            'Test purchase completed successfully.'
        );
    }
}