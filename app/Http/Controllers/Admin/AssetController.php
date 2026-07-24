<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssetFileRole;
use App\Enums\AssetFileRelationshipType;
use App\Enums\AssetFulfillmentType;
use App\Enums\AssetConfigurationDisplayType;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\AssetConfigurationTemplate;
use App\Services\AssetConfigurationTemplateService;
use App\Models\LicenseType;
use App\Services\AssetOfferingService;
use App\Commerce\Configuration\ConfigurationManager;
use App\Services\AssetHealthService;
use App\Services\AssetAiAssistantService;
use App\Services\AssetMediaPresentationService;
use App\Services\AssetPresentationService;
use App\Services\AssetFileRelationshipService;
use App\Models\Collection;
use App\Models\Tag;
use App\Services\AssetService;
use App\Services\AssetTagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $type = $request->string('asset_type')->toString();
        $status = $request->string('status')->toString();

        $assets = Asset::query()
            ->withCount(['files', 'activeFiles'])
            ->with(['collection:id,name', 'primaryPreviewFile', 'activeFiles', 'offerings', 'tags:id,name'])
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->when($type, fn ($query) => $query->where('asset_type', $type))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(fn (Asset $asset) => $this->formatAsset($asset));

        return Inertia::render('Admin/Assets/Index', [
            'assets' => $assets,
            'filters' => compact('search', 'type', 'status'),
            'assetTypes' => $this->assetTypes(),
            'statuses' => $this->statuses(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Assets/Create', $this->formOptions());
    }

    public function store(Request $request, AssetService $assetService, ConfigurationManager $configurationService, AssetPresentationService $presentationService, AssetTagService $tagService): RedirectResponse
    {
        $validated = $this->validateAsset($request, requireFiles: true);

        $asset = DB::transaction(function () use ($request, $validated, $assetService, $configurationService, $presentationService, $tagService): Asset {
            $asset = $assetService->create([
                'collection_id' => $validated['collection_id'] ?? null,
                'title' => $validated['title'],
                'slug' => $this->uniqueSlug($validated['title']),
                'description' => $validated['description'] ?? null,
                'asset_type' => $validated['asset_type'],
                'status' => $validated['status'],
                'photographer' => $validated['photographer'] ?? null,
                'sort_order' => $validated['sort_order'],
                'is_active' => $request->boolean('is_active'),
                'is_featured' => $request->boolean('is_featured'),
                'is_ai_generated' => $request->boolean('is_ai_generated'),
                'allows_quantity' => $request->boolean('allows_quantity'),
                'fulfillment_type' => $validated['fulfillment_type'],
                'collects_shipping_address' => $request->boolean('collects_shipping_address'),
                'shipping_address_required' => $request->boolean('collects_shipping_address')
                    && $request->boolean('shipping_address_required'),
            ]);

            $uploadedAssetFiles = [];

            foreach ($request->file('files', []) as $index => $file) {
                $role = AssetFileRole::from($validated['file_roles'][$index] ?? AssetFileRole::Supplemental->value);
                $assetFile = $assetService->addFile($asset, $file, $role, attributes: [
                    'sort_order' => ($index + 1) * 10,
                    'is_downloadable' => (bool) ($validated['file_downloadable'][$index] ?? true),
                ]);

                $uploadedAssetFiles[$index] = $assetFile;

                if (($validated['primary_preview_index'] ?? null) !== null && (int) $validated['primary_preview_index'] === $index) {
                    $assetService->setPrimaryPreview($asset, $assetFile);
                }

                if (($validated['poster_index'] ?? null) !== null && (int) $validated['poster_index'] === $index) {
                    $assetService->setPoster($asset, $assetFile);
                }
            }

            if ($request->hasFile('marketplace_image')) {
                $sourceIndex = $validated['marketplace_source_index'] ?? null;
                $sourceAssetFileId = $sourceIndex !== null
                    ? ($uploadedAssetFiles[(int) $sourceIndex]->id ?? null)
                    : null;

                $presentationService->saveMarketplace(
                    $asset,
                    $request->file('marketplace_image'),
                    $this->decodePresentationData(
                        $validated['marketplace_edit_data'] ?? null,
                    ),
                    $sourceAssetFileId,
                );
            }

            $configurationService->saveMany($asset, $validated['configurations'] ?? []);
            $tagService->syncNames($asset, $validated['tag_names'] ?? []);

            return $asset;
        });

        return redirect()->route('admin.assets.edit', $asset)
            ->with('success', 'Asset created and files uploaded successfully.');
    }

    public function edit(Asset $asset): Response
    {
        $asset->load([
            'activeFiles',
            'collection',
            'fileRelationships.sourceFile',
            'fileRelationships.targetFile',
            'offerings.files',
            'offerings.licenseType',
            'offerings.pricingTiers',
            'configurationGroups.values.rules',
            'pricingTiers',
            'aiSuggestions.requestedBy:id,name',
            'tags:id,name',
        ]);

        return Inertia::render('Admin/Assets/Edit', [
            ...$this->formOptions(),
            'assetRecord' => $this->formatAsset($asset, detailed: true),
            'licenseTypes' => LicenseType::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'description', 'price_cents', 'image_unit_price_cents', 'video_unit_price_cents', 'minimum_price_cents', 'currency', 'download_limit', 'expires_after_days']),
        ]);
    }

    public function update(Request $request, Asset $asset, AssetTagService $tagService): RedirectResponse
    {
        $validated = $this->validateAsset($request, requireFiles: false, asset: $asset);

        $asset->update([
            'collection_id' => $validated['collection_id'] ?? null,
            'title' => $validated['title'],
            'slug' => $this->uniqueSlug($validated['title'], $asset->id),
            'description' => $validated['description'] ?? null,
            'alt_text' => $validated['alt_text'] ?? null,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'dominant_colors' => $validated['dominant_colors'] ?? [],
            'detected_objects' => $validated['detected_objects'] ?? [],
            'asset_type' => $validated['asset_type'],
            'status' => $validated['status'],
            'photographer' => $validated['photographer'] ?? null,
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured'),
            'is_ai_generated' => $request->boolean('is_ai_generated'),
            'allows_quantity' => $request->boolean('allows_quantity'),
            'fulfillment_type' => $validated['fulfillment_type'],
            'collects_shipping_address' => $request->boolean('collects_shipping_address'),
            'shipping_address_required' => $request->boolean('collects_shipping_address')
                && $request->boolean('shipping_address_required'),
        ]);

        $tagService->syncNames($asset, $validated['tag_names'] ?? []);

        return back()->with('success', 'Asset details updated successfully.');
    }

    public function addFiles(Request $request, Asset $asset, AssetService $assetService): RedirectResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:25'],
            'files.*' => ['required', 'file', 'max:'.config('asset-media.max_upload_kilobytes', 512000)],
            'file_roles' => ['required', 'array', 'size:'.count($request->file('files', []))],
            'file_roles.*' => ['required', Rule::enum(AssetFileRole::class)],
            'file_downloadable' => ['nullable', 'array', 'size:'.count($request->file('files', []))],
            'file_downloadable.*' => ['nullable', 'boolean'],
        ]);

        foreach ($request->file('files', []) as $index => $file) {
            $assetService->addFile(
                $asset,
                $file,
                AssetFileRole::from($validated['file_roles'][$index]),
                attributes: [
                    'sort_order' => ((int) $asset->files()->max('sort_order')) + (($index + 1) * 10),
                    'is_downloadable' => (bool) ($validated['file_downloadable'][$index] ?? true),
                ],
            );
        }

        return back()->with('success', 'Files added successfully.');
    }

    public function updateFile(Request $request, Asset $asset, AssetFile $assetFile, AssetService $assetService): RedirectResponse
    {
        $this->assertFileBelongsToAsset($asset, $assetFile);

        $validated = $request->validate([
            'role' => ['required', Rule::enum(AssetFileRole::class)],
            'is_downloadable' => ['boolean'],
            'is_active' => ['boolean'],
            'primary_preview' => ['boolean'],
            'poster' => ['boolean'],
        ]);

        $assetFile->update([
            'role' => $validated['role'],
            'is_downloadable' => $request->boolean('is_downloadable'),
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->boolean('primary_preview')) {
            $assetService->setPrimaryPreview($asset, $assetFile);
        } elseif ($asset->primary_preview_file_id === $assetFile->id) {
            $assetService->setPrimaryPreview($asset, null);
        }

        if ($request->boolean('poster')) {
            $assetService->setPoster($asset, $assetFile);
        } elseif ($asset->poster_file_id === $assetFile->id) {
            $assetService->setPoster($asset, null);
        }

        return back()->with('success', 'File settings updated.');
    }

    public function replaceFile(Request $request, Asset $asset, AssetFile $assetFile, AssetService $assetService): RedirectResponse
    {
        $this->assertFileBelongsToAsset($asset, $assetFile);
        $request->validate(['file' => ['required', 'file', 'max:'.config('asset-media.max_upload_kilobytes', 512000)]]);

        $replacement = $assetService->replaceFile($assetFile, $request->file('file'));

        if ($asset->primary_preview_file_id === $assetFile->id) {
            $assetService->setPrimaryPreview($asset, $replacement);
        }
        if ($asset->poster_file_id === $assetFile->id) {
            $assetService->setPoster($asset, $replacement);
        }

        return back()->with('success', 'File replaced while preserving the previous revision.');
    }

    public function reorderFiles(Request $request, Asset $asset): RedirectResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array'],
            'files.*.id' => ['required', 'integer'],
            'files.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $allowedIds = $asset->files()->withTrashed()->pluck('id')->all();

        DB::transaction(function () use ($validated, $allowedIds): void {
            foreach ($validated['files'] as $item) {
                abort_unless(in_array((int) $item['id'], $allowedIds, true), 422);
                AssetFile::withTrashed()->whereKey($item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        });

        return back()->with('success', 'File order updated.');
    }

    public function destroyFile(Asset $asset, AssetFile $assetFile, AssetService $assetService): RedirectResponse
    {
        $this->assertFileBelongsToAsset($asset, $assetFile);
        $assetService->removeFile($assetFile);

        return back()->with('success', 'File removed. The physical revision was retained.');
    }



    public function updatePresentation(
        Request $request,
        Asset $asset,
        AssetPresentationService $service,
    ): RedirectResponse {
        $validated = $request->validate([
            'marketplace_image' => [
                'nullable',
                'file',
                'image',
                'max:'.config('asset-media.max_upload_kilobytes', 512000),
            ],
            'marketplace_edit_data' => ['nullable', 'string', 'max:20000'],
            'marketplace_source_asset_file_id' => [
                'nullable',
                'integer',
                Rule::exists('asset_files', 'id')->where(
                    fn ($query) => $query->where('asset_id', $asset->id),
                ),
            ],
            'remove_marketplace_image' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_marketplace_image')) {
            $service->clearMarketplace($asset);

            return back()->with(
                'success',
                'The marketplace image was removed. The automatic asset preview will be used.',
            );
        }

        if (! $request->hasFile('marketplace_image')) {
            return back()->withErrors([
                'marketplace_image' => 'Create or edit the marketplace crop before saving.',
            ]);
        }

        $service->saveMarketplace(
            $asset,
            $request->file('marketplace_image'),
            $this->decodePresentationData(
                $validated['marketplace_edit_data'] ?? null,
            ),
            isset($validated['marketplace_source_asset_file_id'])
                ? (int) $validated['marketplace_source_asset_file_id']
                : null,
        );

        return back()->with('success', 'Marketplace image updated successfully.');
    }


    public function updateRelationships(
        Request $request,
        Asset $asset,
        AssetFileRelationshipService $service,
    ): RedirectResponse {
        $validated = $request->validate([
            'relationships' => ['array', 'max:250'],
            'relationships.*.source_asset_file_id' => [
                'required',
                'integer',
                Rule::exists('asset_files', 'id')->where(
                    fn ($query) => $query->where('asset_id', $asset->id),
                ),
            ],
            'relationships.*.target_asset_file_id' => [
                'required',
                'integer',
                Rule::exists('asset_files', 'id')->where(
                    fn ($query) => $query->where('asset_id', $asset->id),
                ),
                'different:relationships.*.source_asset_file_id',
            ],
            'relationships.*.relationship_type' => [
                'required',
                Rule::enum(AssetFileRelationshipType::class),
            ],
            'relationships.*.label' => [
                'nullable',
                'string',
                'max:255',
            ],
            'relationships.*.sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'relationships.*.metadata' => [
                'nullable',
                'array',
            ],
        ]);

        $service->saveMany(
            $asset,
            $validated['relationships'] ?? [],
        );

        return back()->with(
            'success',
            'File relationships updated successfully.',
        );
    }

    public function updateOfferings(Request $request, Asset $asset, AssetOfferingService $service): RedirectResponse
    {
        $validated = $request->validate([
            'offerings' => ['array', 'max:50'],
            'offerings.*.license_type_id' => ['required', 'integer', 'exists:license_types,id'],
            'offerings.*.name' => ['required', 'string', 'max:255'],
            'offerings.*.description' => ['nullable', 'string'],
            'offerings.*.image_units' => ['required', 'integer', 'min:0'],
            'offerings.*.video_units' => ['required', 'integer', 'min:0'],
            'offerings.*.price_adjustment_cents' => ['nullable', 'integer'],
            'offerings.*.price_override_cents' => ['nullable', 'integer', 'min:0'],
            'offerings.*.price_cents' => ['nullable', 'integer', 'min:0'],
            'offerings.*.currency' => ['required', 'string', 'size:3'],
            'offerings.*.download_limit' => ['nullable', 'integer', 'min:1'],
            'offerings.*.expires_after_days' => ['nullable', 'integer', 'min:1'],
            'offerings.*.include_all_active_files' => ['boolean'],
            'offerings.*.is_active' => ['boolean'],
            'offerings.*.file_ids' => ['array'],
            'offerings.*.file_ids.*' => ['integer'],
            'offerings.*.pricing_tiers' => ['nullable', 'array', 'max:50'],
            'offerings.*.pricing_tiers.*.minimum_quantity' => ['required', 'integer', 'min:1'],
            'offerings.*.pricing_tiers.*.maximum_quantity' => ['nullable', 'integer', 'gte:offerings.*.pricing_tiers.*.minimum_quantity'],
            'offerings.*.pricing_tiers.*.pricing_type' => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\AssetPricingTierType::class)],
            'offerings.*.pricing_tiers.*.unit_price_cents' => ['nullable', 'integer', 'min:0'],
            'offerings.*.pricing_tiers.*.percentage_off' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'offerings.*.pricing_tiers.*.currency' => ['nullable', 'string', 'size:3'],
            'offerings.*.pricing_tiers.*.is_active' => ['boolean'],
        ]);

        $service->saveMany($asset, $validated['offerings'] ?? []);

        return back()->with('success', 'Asset license offerings updated successfully.');
    }

    public function updateConfigurations(Request $request, Asset $asset, ConfigurationManager $service): RedirectResponse
    {
        $validated = $this->validateConfigurations($request);
        $service->saveMany($asset, $validated['configurations'] ?? []);

        return back()->with('success', 'Product configuration updated successfully.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $asset->delete();

        return redirect()->route('admin.assets.index')->with('success', 'Asset archived successfully.');
    }

    private function validateAsset(Request $request, bool $requireFiles, ?Asset $asset = null): array
    {
        return $request->validate([
            'collection_id' => ['nullable', 'exists:collections,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'alt_text' => ['nullable', 'string', 'max:1000'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:1000'],
            'dominant_colors' => ['nullable', 'array', 'max:20'],
            'dominant_colors.*' => ['required', 'string', 'max:100'],
            'detected_objects' => ['nullable', 'array', 'max:50'],
            'detected_objects.*' => ['required', 'string', 'max:100'],
            'photographer' => ['nullable', 'string', 'max:255'],
            'asset_type' => ['required', Rule::enum(AssetType::class)],
            'status' => ['required', Rule::enum(AssetStatus::class)],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'is_ai_generated' => ['boolean'],
            'allows_quantity' => ['boolean'],
            'fulfillment_type' => ['required', Rule::enum(AssetFulfillmentType::class)],
            'collects_shipping_address' => ['boolean'],
            'shipping_address_required' => ['boolean'],
            'tag_names' => ['nullable', 'array', 'max:50'],
            'tag_names.*' => ['required', 'string', 'max:100'],
            'files' => [$requireFiles ? 'required' : 'nullable', 'array', $requireFiles ? 'min:1' : 'min:0', 'max:25'],
            'files.*' => ['file', 'max:'.config('asset-media.max_upload_kilobytes', 512000)],
            'file_roles' => [$requireFiles ? 'required' : 'nullable', 'array'],
            'file_roles.*' => ['required', Rule::enum(AssetFileRole::class)],
            'file_downloadable' => ['nullable', 'array'],
            'primary_preview_index' => ['nullable', 'integer', 'min:0'],
            'poster_index' => ['nullable', 'integer', 'min:0'],
            'marketplace_image' => [
                'nullable',
                'file',
                'image',
                'max:'.config('asset-media.max_upload_kilobytes', 512000),
            ],
            'marketplace_edit_data' => ['nullable', 'string', 'max:20000'],
            'marketplace_source_index' => ['nullable', 'integer', 'min:0'],
            ...$this->configurationRules(),
        ]);
    }

    private function formOptions(): array
    {
        return [
            'imageTags' => Tag::query()->where('tag_type', 'image')->orderBy('name')->get(['id', 'name']),
            'collections' => Collection::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'assetTypes' => $this->assetTypes(),
            'statuses' => $this->statuses(),
            'fileRoles' => collect(AssetFileRole::cases())->map(fn ($role) => ['value' => $role->value, 'label' => Str::headline($role->value)])->values(),
            'acceptedExtensions' => collect(config('asset-media.extensions', []))->flatten()->unique()->values(),
            'maxUploadKilobytes' => config('asset-media.max_upload_kilobytes', 512000),
            'relationshipTypes' => collect(
                AssetFileRelationshipType::cases(),
            )->map(fn (AssetFileRelationshipType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'description' => $type->description(),
            ])->values(),
            'fulfillmentTypes' => collect(AssetFulfillmentType::cases())->map(fn ($type) => ['value' => $type->value, 'label' => $type->label()])->values(),
            'configurationDisplayTypes' => collect(AssetConfigurationDisplayType::cases())->map(fn ($type) => ['value' => $type->value, 'label' => $type->label(), 'uses_values' => $type->usesValues()])->values(),
            'configurationTemplates' => AssetConfigurationTemplate::query()
                ->with('activeValues')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(function (AssetConfigurationTemplate $template): array {
                    return [
                        'id' => $template->id,
                        'name' => $template->name,
                        'code' => $template->code,
                        'description' => $template->description,
                        'display_type' => $template->display_type->value,
                        'display_type_label' => $template->display_type->label(),
                        'values_count' => $template->activeValues->count(),
                        'asset_group' => app(AssetConfigurationTemplateService::class)->toAssetGroup($template),
                    ];
                })->values(),
        ];
    }

    public function previewFile(
        Asset $asset,
        AssetFile $assetFile,
        AssetMediaPresentationService $presentation,
    ): BinaryFileResponse|StreamedResponse|HttpResponse {
        $this->assertFileBelongsToAsset($asset, $assetFile);
        abort_unless($assetFile->is_active, 404);

        return $presentation->response($assetFile);
    }

    private function validateConfigurations(Request $request): array
    {
        return $request->validate($this->configurationRules());
    }

    private function configurationRules(): array
    {
        return [
            'configurations' => ['nullable', 'array', 'max:30'],
            'configurations.*.name' => ['required', 'string', 'max:255'],
            'configurations.*.code' => ['nullable', 'string', 'max:255'],
            'configurations.*.display_type' => ['required', Rule::enum(AssetConfigurationDisplayType::class)],
            'configurations.*.is_required' => ['boolean'],
            'configurations.*.allows_multiple' => ['boolean'],
            'configurations.*.placeholder' => ['nullable', 'string', 'max:255'],
            'configurations.*.help_text' => ['nullable', 'string', 'max:2000'],
            'configurations.*.minimum_value' => ['nullable', 'numeric'],
            'configurations.*.maximum_value' => ['nullable', 'numeric'],
            'configurations.*.step_value' => ['nullable', 'numeric', 'gt:0'],
            'configurations.*.is_active' => ['boolean'],
            'configurations.*.values' => ['nullable', 'array', 'max:100'],
            'configurations.*.values.*.label' => ['required_with:configurations.*.values', 'string', 'max:255'],
            'configurations.*.values.*.value' => ['nullable', 'string', 'max:255'],
            'configurations.*.values.*.description' => ['nullable', 'string', 'max:2000'],
            'configurations.*.values.*.swatch_color' => ['nullable', 'string', 'max:32'],
            'configurations.*.values.*.image_path' => ['nullable', 'string', 'max:1024'],
            'configurations.*.values.*.is_default' => ['boolean'],
            'configurations.*.values.*.is_active' => ['boolean'],
            'configurations.*.values.*.price_adjustment_cents' => ['nullable', 'integer', 'min:-100000000', 'max:100000000'],
            'configurations.*.values.*.currency' => ['nullable', 'string', 'size:3'],
        ];
    }


    private function decodePresentationData(?string $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function assetTypes(): array
    {
        return collect(AssetType::cases())->map(fn ($type) => ['value' => $type->value, 'label' => $type->label()])->values()->all();
    }

    private function statuses(): array
    {
        return collect(AssetStatus::cases())->map(fn ($status) => ['value' => $status->value, 'label' => Str::headline($status->value)])->values()->all();
    }

    private function formatAsset(Asset $asset, bool $detailed = false): array
    {
        $data = [
            'id' => $asset->id,
            'uuid' => $asset->uuid,
            'title' => $asset->title,
            'slug' => $asset->slug,
            'description' => $asset->description,
            'alt_text' => $asset->alt_text,
            'seo_title' => $asset->seo_title,
            'seo_description' => $asset->seo_description,
            'keywords' => $asset->tags->pluck('name')->values()->all() ?: ($asset->keywords ?? []),
            'dominant_colors' => $asset->dominant_colors ?? [],
            'detected_objects' => $asset->detected_objects ?? [],
            'collection_id' => $asset->collection_id,
            'collection' => $asset->collection ? ['id' => $asset->collection->id, 'name' => $asset->collection->name] : null,
            'asset_type' => $asset->asset_type->value,
            'status' => $asset->status->value,
            'photographer' => $asset->photographer,
            'sort_order' => $asset->sort_order,
            'is_active' => $asset->is_active,
            'is_featured' => $asset->is_featured,
            'is_ai_generated' => $asset->is_ai_generated,
            'allows_quantity' => $asset->allows_quantity,
            'fulfillment_type' => $asset->fulfillment_type->value,
            'collects_shipping_address' => $asset->collects_shipping_address,
            'shipping_address_required' => $asset->shipping_address_required,
            'files_count' => $asset->files_count ?? $asset->files()->count(),
            'active_files_count' => $asset->active_files_count ?? $asset->activeFiles()->count(),
            'primary_preview_file_id' => $asset->primary_preview_file_id,
            'poster_file_id' => $asset->poster_file_id,
            'preview_url' => $asset->primaryPreviewFile?->publicUrl(),
            'marketplace_image_url' => app(AssetPresentationService::class)
                ->marketplaceUrl($asset),
            'marketplace_image_edit_data' => data_get(
                $asset->presentation_images,
                'marketplace.edit_data',
            ),
            'marketplace_source_asset_file_id' => data_get(
                $asset->presentation_images,
                'marketplace.source_asset_file_id',
            ),
            'legacy_image_id' => $asset->legacy_image_id,
            'health' => app(AssetHealthService::class)->summarize($asset),
        ];

        if ($detailed) {
            $presentation = app(AssetMediaPresentationService::class);
            $posterUrl = $asset->posterFile ? $presentation->url($asset, $asset->posterFile, true) : null;

            $data['ai_suggestions'] = $asset->aiSuggestions->map(fn ($suggestion) => [
                'id' => $suggestion->id,
                'status' => $suggestion->status,
                'provider' => $suggestion->provider,
                'model' => $suggestion->model,
                'suggestions' => $suggestion->suggestions,
                'local_analysis' => $suggestion->local_analysis,
                'error_message' => $suggestion->error_message,
                'total_tokens' => $suggestion->total_tokens,
                'requested_by' => $suggestion->requestedBy?->name,
                'created_at' => $suggestion->created_at?->toISOString(),
                'completed_at' => $suggestion->completed_at?->toISOString(),
                'reviewed_at' => $suggestion->reviewed_at?->toISOString(),
            ])->values();
            $assistant = app(AssetAiAssistantService::class);
            $data['ai_assistant_enabled'] = $assistant->isEnabled();
            $data['ai_default_provider'] = $assistant->defaultProvider();
            $data['ai_providers'] = $assistant->availableProviders();

            $data['files'] = $asset->activeFiles->map(fn (AssetFile $file) => [
                'id' => $file->id,
                'uuid' => $file->uuid,
                'role' => $file->role->value,
                'media_type' => $file->media_type->value,
                'original_filename' => $file->original_filename,
                'extension' => $file->extension,
                'mime_type' => $file->mime_type,
                'size_bytes' => $file->size_bytes,
                'sort_order' => $file->sort_order,
                'width' => $file->width,
                'height' => $file->height,
                'duration_seconds' => $file->duration_seconds,
                'processing_status' => $file->processing_status->value,
                'virus_scan_status' => $file->virus_scan_status->value,
                'is_downloadable' => $file->is_downloadable,
                'is_active' => $file->is_active,
                'is_legacy' => $file->is_legacy,
                'is_primary_preview' => $asset->primary_preview_file_id === $file->id,
                'is_poster' => $asset->poster_file_id === $file->id,
                'public_url' => $file->publicUrl(),
                'can_preview' => $presentation->canPreview($file),
                'preview_kind' => $presentation->previewKind($file),
                'preview_url' => $presentation->canPreview($file) ? $presentation->url($asset, $file, true) : null,
                'poster_url' => $presentation->previewKind($file) === 'video' ? $posterUrl : null,
                'preview_note' => $presentation->format($asset, $file, $posterUrl, true)['preview_note'],
            ])->values();
            $data['file_relationships'] = $asset->fileRelationships
                ->map(fn ($relationship) => [
                    'id' => $relationship->id,
                    'source_asset_file_id' =>
                        $relationship->source_asset_file_id,
                    'target_asset_file_id' =>
                        $relationship->target_asset_file_id,
                    'relationship_type' =>
                        $relationship->relationship_type->value,
                    'label' => $relationship->label,
                    'sort_order' => $relationship->sort_order,
                    'metadata' => $relationship->metadata,
                ])->values();

            $data['offerings'] = $asset->offerings->map(fn ($offering) => [
                'id' => $offering->id,
                'license_type_id' => $offering->license_type_id,
                'name' => $offering->name,
                'description' => $offering->description,
                'image_units' => $offering->image_units,
                'video_units' => $offering->video_units,
                'price_adjustment_cents' => $offering->price_adjustment_cents,
                'price_override_cents' => $offering->price_override_cents,
                'price_cents' => $offering->price_cents,
                'currency' => $offering->currency,
                'download_limit' => $offering->download_limit,
                'expires_after_days' => $offering->expires_after_days,
                'include_all_active_files' => $offering->include_all_active_files,
                'is_active' => $offering->is_active,
                'file_ids' => $offering->files->pluck('id')->values(),
                'pricing_tiers' => $offering->pricingTiers->map(fn ($tier) => [
                    'id' => $tier->id,
                    'minimum_quantity' => $tier->minimum_quantity,
                    'maximum_quantity' => $tier->maximum_quantity,
                    'pricing_type' => $tier->pricing_type->value,
                    'unit_price_cents' => $tier->unit_price_cents,
                    'percentage_off' => $tier->percentage_off !== null ? (float) $tier->percentage_off : null,
                    'currency' => $tier->currency,
                    'is_active' => $tier->is_active,
                ])->values(),
            ])->values();
            $data['configurations'] = $asset->configurationGroups->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'code' => $group->code,
                'display_type' => $group->display_type->value,
                'is_required' => $group->is_required,
                'allows_multiple' => $group->allows_multiple,
                'placeholder' => $group->placeholder,
                'help_text' => $group->help_text,
                'minimum_value' => $group->minimum_value,
                'maximum_value' => $group->maximum_value,
                'step_value' => $group->step_value,
                'is_active' => $group->is_active,
                'values' => $group->values->map(fn ($value) => [
                    'id' => $value->id,
                    'label' => $value->label,
                    'value' => $value->value,
                    'description' => $value->description,
                    'swatch_color' => $value->swatch_color,
                    'image_path' => $value->image_path,
                    'is_default' => $value->is_default,
                    'is_active' => $value->is_active,
                    'price_adjustment_cents' => (int) ($value->rules->firstWhere('rule_type.value', 'fixed_adjustment')?->amount_cents ?? $value->rules->first()?->amount_cents ?? 0),
                    'currency' => $value->rules->first()?->currency ?? 'USD',
                ])->values(),
            ])->values();
        }

        return $data;
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: Str::uuid()->toString();
        $slug = $base;
        $counter = 2;
        while (Asset::query()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }
        return $slug;
    }

    private function assertFileBelongsToAsset(Asset $asset, AssetFile $assetFile): void
    {
        abort_unless($assetFile->asset_id === $asset->id, 404);
    }
}
