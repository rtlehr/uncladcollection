<?php

namespace App\Http\Controllers;

use App\Commerce\Cart\CartEngine;
use App\Models\AssetOffering;
use App\Models\CartItem;
use App\Models\Image;
use App\Models\LicenseType;
use App\Services\StripeCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(Request $request): Response
    {
        $cartItems = CartItem::query()
            ->with([
                'image',
                'licenseType:id,name,description,price_cents,currency',
                'asset.primaryPreviewFile',
                'assetOffering.licenseType:id,name,description,price_cents,currency',
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(function (CartItem $cartItem): array {
                if ($cartItem->asset_id && $cartItem->asset && $cartItem->assetOffering) {
                    $preview = $cartItem->asset->primaryPreviewFile;

                    return [
                        'id' => $cartItem->id,
                        'kind' => 'asset',
                        'quantity' => $cartItem->quantity,
                        'unit_price_cents' => $cartItem->final_unit_price_cents ?? $cartItem->price_cents,
                        'line_total_cents' => $cartItem->line_total_cents ?? $cartItem->price_cents,
                        'currency' => $cartItem->currency,
                        'configuration' => $cartItem->configuration_snapshot,
                        'pricing' => $cartItem->pricing_snapshot,
                        'asset' => [
                            'id' => $cartItem->asset->id,
                            'title' => $cartItem->asset->title,
                            'slug' => $cartItem->asset->slug,
                            'preview_url' => $preview ? route('assets.preview', [$cartItem->asset, $preview]) : null,
                        ],
                        'offering' => [
                            'id' => $cartItem->assetOffering->id,
                            'name' => $cartItem->assetOffering->name,
                            'description' => $cartItem->assetOffering->description,
                        ],
                    ];
                }

                return [
                    'id' => $cartItem->id,
                    'kind' => 'legacy_image',
                    'quantity' => 1,
                    'unit_price_cents' => $cartItem->price_cents,
                    'line_total_cents' => $cartItem->price_cents,
                    'currency' => $cartItem->currency,
                    'configuration' => null,
                    'pricing' => null,
                    'image' => [
                        'id' => $cartItem->image->id,
                        'title' => $cartItem->image->title,
                        'slug' => $cartItem->image->slug,
                        'preview_url' => $cartItem->image->thumbnail_url
                            ?? $cartItem->image->icon_url
                            ?? $cartItem->image->high_res_url
                            ?? $cartItem->image->original_url,
                    ],
                    'license_type' => [
                        'id' => $cartItem->licenseType->id,
                        'name' => $cartItem->licenseType->name,
                        'description' => $cartItem->licenseType->description,
                    ],
                ];
            });

        return Inertia::render('Cart/Index', [
            'cartItems' => $cartItems,
            'cartTotalCents' => $cartItems->sum('line_total_cents'),
            'containsAssetItems' => $cartItems->contains(fn (array $item) => $item['kind'] === 'asset'),
            'assetCheckoutEnabled' => true,
        ]);
    }

    public function store(Request $request, CartEngine $cartEngine): RedirectResponse
    {
        if ($request->filled('asset_offering_id')) {
            $validated = $request->validate([
                'asset_offering_id' => ['required', 'integer', 'exists:asset_offerings,id'],
                'lines' => ['required', 'array', 'min:1', 'max:50'],
                'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
                'lines.*.selections' => ['nullable', 'array'],
            ]);

            $offering = AssetOffering::query()
                ->where('is_active', true)
                ->with('asset')
                ->findOrFail($validated['asset_offering_id']);

            $cartEngine->addAssetLines($request->user(), $offering, $validated['lines']);

            return back()->with('success', 'Configured items added to your cart.');
        }

        $validated = $request->validate([
            'image_id' => ['required', 'integer', 'exists:images,id'],
            'license_type_id' => ['required', 'integer', 'exists:license_types,id'],
        ]);

        $image = Image::query()->where('is_active', true)->findOrFail($validated['image_id']);
        $licenseType = LicenseType::query()->where('is_active', true)->findOrFail($validated['license_type_id']);

        CartItem::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'image_id' => $image->id,
                'license_type_id' => $licenseType->id,
            ],
            [
                'price_cents' => $licenseType->price_cents,
                'quantity' => 1,
                'line_total_cents' => $licenseType->price_cents,
                'currency' => $licenseType->currency,
            ],
        );

        return back()->with('success', 'Asset added to cart.');
    }

    public function update(Request $request, CartItem $cartItem, CartEngine $cartEngine): RedirectResponse
    {
        abort_if($cartItem->user_id !== $request->user()->id, 403);

        if ($cartItem->asset_id) {
            $validated = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:999']]);
            $cartEngine->updateAssetQuantity($cartItem, (int) $validated['quantity']);

            return back()->with('success', 'Cart quantity updated.');
        }

        $validated = $request->validate(['license_type_id' => ['required', 'integer', 'exists:license_types,id']]);
        $licenseType = LicenseType::query()->where('is_active', true)->findOrFail($validated['license_type_id']);
        $cartItem->update([
            'license_type_id' => $licenseType->id,
            'price_cents' => $licenseType->price_cents,
            'line_total_cents' => $licenseType->price_cents,
            'currency' => $licenseType->currency,
        ]);

        return back()->with('success', 'Cart item updated.');
    }

    public function destroy(Request $request, CartItem $cartItem, CartEngine $cartEngine): RedirectResponse
    {
        abort_if($cartItem->user_id !== $request->user()->id, 403);
        $cartEngine->remove($cartItem);

        return back()->with('success', 'Item removed from cart.');
    }

    public function clear(Request $request): RedirectResponse
    {
        CartItem::query()->where('user_id', $request->user()->id)->delete();

        return back()->with('success', 'Cart cleared.');
    }

    public function checkout(Request $request, StripeCheckoutService $stripeCheckoutService): RedirectResponse
    {
        $cartItems = CartItem::query()
            ->with([
                'image',
                'licenseType',
                'asset.configurationGroups.values.rules',
                'assetOffering.asset',
                'assetOffering.licenseType',
                'assetOffering.pricingTiers',
            ])
            ->where('user_id', $request->user()->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return back()->with('error', 'Your cart is empty.');
        }

        $session = $stripeCheckoutService->createCartCheckoutSession($request->user(), $cartItems);

        return redirect()->away($session->url);
    }
}
