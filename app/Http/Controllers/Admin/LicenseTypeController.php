<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LicenseType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LicenseTypeController extends Controller
{
    public function index(): Response
    {
        $licenseTypes = LicenseType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/LicenseTypes/Index', [
            'licenseTypes' => $licenseTypes,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/LicenseTypes/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateLicenseType($request);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);
        $validated['image_unit_price_cents'] = (int) round(((float) $validated['image_unit_price']) * 100);
        $validated['video_unit_price_cents'] = (int) round(((float) $validated['video_unit_price']) * 100);
        $validated['minimum_price_cents'] = filled($validated['minimum_price'] ?? null)
            ? (int) round(((float) $validated['minimum_price']) * 100)
            : null;
        $validated['price_cents'] = $validated['image_unit_price_cents'];

        unset($validated['image_unit_price'], $validated['video_unit_price'], $validated['minimum_price']);

        LicenseType::create($validated);

        return redirect()
            ->route('admin.license-types.index')
            ->with('success', 'License type created successfully.');
    }

    public function edit(LicenseType $licenseType): Response
    {
        return Inertia::render('Admin/LicenseTypes/Edit', [
            'licenseType' => [
                ...$licenseType->toArray(),
                'image_unit_price' => number_format($licenseType->image_unit_price_cents / 100, 2, '.', ''),
                'video_unit_price' => number_format($licenseType->video_unit_price_cents / 100, 2, '.', ''),
                'minimum_price' => $licenseType->minimum_price_cents !== null
                    ? number_format($licenseType->minimum_price_cents / 100, 2, '.', '')
                    : '',
            ],
        ]);
    }

    public function update(Request $request, LicenseType $licenseType): RedirectResponse
    {
        $validated = $this->validateLicenseType($request, $licenseType->id);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']);
        $validated['image_unit_price_cents'] = (int) round(((float) $validated['image_unit_price']) * 100);
        $validated['video_unit_price_cents'] = (int) round(((float) $validated['video_unit_price']) * 100);
        $validated['minimum_price_cents'] = filled($validated['minimum_price'] ?? null)
            ? (int) round(((float) $validated['minimum_price']) * 100)
            : null;
        $validated['price_cents'] = $validated['image_unit_price_cents'];

        unset($validated['image_unit_price'], $validated['video_unit_price'], $validated['minimum_price']);

        $licenseType->update($validated);

        return redirect()
            ->route('admin.license-types.index')
            ->with('success', 'License type updated successfully.');
    }

    public function destroy(LicenseType $licenseType): RedirectResponse
    {
        $licenseType->delete();

        return redirect()
            ->route('admin.license-types.index')
            ->with('success', 'License type deleted successfully.');
    }

    private function validateLicenseType(Request $request, ?int $licenseTypeId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'unique:license_types,slug,' . $licenseTypeId,
            ],
            'description' => ['nullable', 'string'],
            'image_unit_price' => ['required', 'numeric', 'min:0'],
            'video_unit_price' => ['required', 'numeric', 'min:0'],
            'minimum_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'download_limit' => ['nullable', 'integer', 'min:1'],
            'expires_after_days' => ['nullable', 'integer', 'min:1'],
            'max_resolution' => ['required', 'string', 'max:50'],
            'usage_terms' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);
    }
}