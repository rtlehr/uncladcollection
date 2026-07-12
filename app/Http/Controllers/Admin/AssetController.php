<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssetFileRole;
use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\Collection;
use App\Services\AssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $type = $request->string('asset_type')->toString();
        $status = $request->string('status')->toString();

        $assets = Asset::query()
            ->withCount(['files', 'activeFiles'])
            ->with(['collection:id,name', 'primaryPreviewFile'])
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

    public function store(Request $request, AssetService $assetService): RedirectResponse
    {
        $validated = $this->validateAsset($request, requireFiles: true);

        $asset = DB::transaction(function () use ($request, $validated, $assetService): Asset {
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
            ]);

            foreach ($request->file('files', []) as $index => $file) {
                $role = AssetFileRole::from($validated['file_roles'][$index] ?? AssetFileRole::Supplemental->value);
                $assetFile = $assetService->addFile($asset, $file, $role, attributes: [
                    'sort_order' => ($index + 1) * 10,
                    'is_downloadable' => (bool) ($validated['file_downloadable'][$index] ?? true),
                ]);

                if (($validated['primary_preview_index'] ?? null) !== null && (int) $validated['primary_preview_index'] === $index) {
                    $assetService->setPrimaryPreview($asset, $assetFile);
                }

                if (($validated['poster_index'] ?? null) !== null && (int) $validated['poster_index'] === $index) {
                    $assetService->setPoster($asset, $assetFile);
                }
            }

            return $asset;
        });

        return redirect()->route('admin.assets.edit', $asset)
            ->with('success', 'Asset created and files uploaded successfully.');
    }

    public function edit(Asset $asset): Response
    {
        $asset->load(['activeFiles', 'collection']);

        return Inertia::render('Admin/Assets/Edit', [
            ...$this->formOptions(),
            'assetRecord' => $this->formatAsset($asset, detailed: true),
        ]);
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $validated = $this->validateAsset($request, requireFiles: false, asset: $asset);

        $asset->update([
            'collection_id' => $validated['collection_id'] ?? null,
            'title' => $validated['title'],
            'slug' => $this->uniqueSlug($validated['title'], $asset->id),
            'description' => $validated['description'] ?? null,
            'asset_type' => $validated['asset_type'],
            'status' => $validated['status'],
            'photographer' => $validated['photographer'] ?? null,
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured'),
            'is_ai_generated' => $request->boolean('is_ai_generated'),
        ]);

        return back()->with('success', 'Asset details updated successfully.');
    }

    public function addFiles(Request $request, Asset $asset, AssetService $assetService): RedirectResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:25'],
            'files.*' => ['required', 'file', 'max:'.config('asset-media.max_upload_kilobytes', 512000)],
            'file_roles' => ['required', 'array'],
            'file_roles.*' => ['required', Rule::enum(AssetFileRole::class)],
            'file_downloadable' => ['nullable', 'array'],
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
            'photographer' => ['nullable', 'string', 'max:255'],
            'asset_type' => ['required', Rule::enum(AssetType::class)],
            'status' => ['required', Rule::enum(AssetStatus::class)],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'is_ai_generated' => ['boolean'],
            'files' => [$requireFiles ? 'required' : 'nullable', 'array', $requireFiles ? 'min:1' : 'min:0', 'max:25'],
            'files.*' => ['file', 'max:'.config('asset-media.max_upload_kilobytes', 512000)],
            'file_roles' => [$requireFiles ? 'required' : 'nullable', 'array'],
            'file_roles.*' => ['required', Rule::enum(AssetFileRole::class)],
            'file_downloadable' => ['nullable', 'array'],
            'primary_preview_index' => ['nullable', 'integer', 'min:0'],
            'poster_index' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function formOptions(): array
    {
        return [
            'collections' => Collection::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'assetTypes' => $this->assetTypes(),
            'statuses' => $this->statuses(),
            'fileRoles' => collect(AssetFileRole::cases())->map(fn ($role) => ['value' => $role->value, 'label' => Str::headline($role->value)])->values(),
            'acceptedExtensions' => collect(config('asset-media.extensions', []))->flatten()->unique()->values(),
            'maxUploadKilobytes' => config('asset-media.max_upload_kilobytes', 512000),
        ];
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
            'collection_id' => $asset->collection_id,
            'collection' => $asset->collection ? ['id' => $asset->collection->id, 'name' => $asset->collection->name] : null,
            'asset_type' => $asset->asset_type->value,
            'status' => $asset->status->value,
            'photographer' => $asset->photographer,
            'sort_order' => $asset->sort_order,
            'is_active' => $asset->is_active,
            'is_featured' => $asset->is_featured,
            'is_ai_generated' => $asset->is_ai_generated,
            'files_count' => $asset->files_count ?? $asset->files()->count(),
            'active_files_count' => $asset->active_files_count ?? $asset->activeFiles()->count(),
            'primary_preview_file_id' => $asset->primary_preview_file_id,
            'poster_file_id' => $asset->poster_file_id,
            'preview_url' => $asset->primaryPreviewFile?->publicUrl(),
            'legacy_image_id' => $asset->legacy_image_id,
        ];

        if ($detailed) {
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
