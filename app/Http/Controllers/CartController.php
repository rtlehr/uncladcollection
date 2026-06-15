<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Image;
use App\Models\LicenseType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\StripeCheckoutService;

class CartController extends Controller
{
    public function index(Request $request): Response
    {
        $cartItems = CartItem::query()
            ->with([
                'image',
                'licenseType:id,name,description,price_cents,currency',
            ])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (CartItem $cartItem) => [
                'id' => $cartItem->id,
                'price_cents' => $cartItem->price_cents,
                'currency' => $cartItem->currency,
                'image' => [
                    'id' => $cartItem->image->id,
                    'title' => $cartItem->image->title,
                    'slug' => $cartItem->image->slug,
                    'thumbnail_url' => $cartItem->image->thumbnail_url,
                    'icon_url' => $cartItem->image->icon_url,
                    'preview_url' => $cartItem->image->thumbnail_url
                        ?? $cartItem->image->icon_url
                        ?? $cartItem->image->high_res_url
                        ?? $cartItem->image->original_url,
                ],
                'license_type' => [
                    'id' => $cartItem->licenseType->id,
                    'name' => $cartItem->licenseType->name,
                    'description' => $cartItem->licenseType->description,
                    'price_cents' => $cartItem->licenseType->price_cents,
                    'currency' => $cartItem->licenseType->currency,
                ],
            ]);

        return Inertia::render('Cart/Index', [
            'cartItems' => $cartItems,
            'cartTotalCents' => $cartItems->sum('price_cents'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'image_id' => ['required', 'integer', 'exists:images,id'],
            'license_type_id' => ['required', 'integer', 'exists:license_types,id'],
        ]);

        $image = Image::query()
            ->where('is_active', true)
            ->findOrFail($validated['image_id']);

        $licenseType = LicenseType::query()
            ->where('is_active', true)
            ->findOrFail($validated['license_type_id']);

        CartItem::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'image_id' => $image->id,
                'license_type_id' => $licenseType->id,
            ],
            [
                'price_cents' => $licenseType->price_cents,
                'currency' => $licenseType->currency,
            ]
        );

        return back()->with('success', 'Image added to cart.');
    }

    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_if($cartItem->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'license_type_id' => ['required', 'integer', 'exists:license_types,id'],
        ]);

        $licenseType = LicenseType::query()
            ->where('is_active', true)
            ->findOrFail($validated['license_type_id']);

        $cartItem->update([
            'license_type_id' => $licenseType->id,
            'price_cents' => $licenseType->price_cents,
            'currency' => $licenseType->currency,
        ]);

        return back()->with('success', 'Cart item updated.');
    }

    public function destroy(Request $request, CartItem $cartItem): RedirectResponse
    {
        abort_if($cartItem->user_id !== $request->user()->id, 403);

        $cartItem->delete();

        return back()->with('success', 'Item removed from cart.');
    }

    public function clear(Request $request): RedirectResponse
    {
        CartItem::query()
            ->where('user_id', $request->user()->id)
            ->delete();

        return back()->with('success', 'Cart cleared.');
    }

    public function checkout(
        Request $request,
        StripeCheckoutService $stripeCheckoutService
    ): RedirectResponse {
        $cartItems = CartItem::query()
            ->with(['image', 'licenseType'])
            ->where('user_id', $request->user()->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return back()->with('error', 'Your cart is empty.');
        }

        $session = $stripeCheckoutService->createCartCheckoutSession(
            $request->user(),
            $cartItems
        );

        return redirect()->away($session->url);
    }
}