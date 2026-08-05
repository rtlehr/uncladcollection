<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\DesignProject;
use App\Models\License;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DesignProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $projects = DesignProject::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'asset.primaryPreviewFile',
                'license',
                'exports' => fn ($query) => $query->latest('completed_at'),
            ])
            ->latest('updated_at')
            ->get()
            ->map(fn (DesignProject $project) => [
                'uuid' => $project->uuid,
                'title' => $project->title,
                'status' => $project->status,
                'updated_at' => $project->updated_at?->diffForHumans(),
                'canvas' => [$project->canvas_width, $project->canvas_height],
                'preview_url' => $project->preview_path
                    ? route('account.designs.preview.show', $project)
                    : ($project->asset?->primaryPreviewFile
                        ? route('assets.preview', [$project->asset, $project->asset->primaryPreviewFile])
                        : null),
                'edit_url' => route('account.designs.edit', $project),
                'export_count' => $project->exports->where('status', 'completed')->count(),
                'latest_exports' => $project->exports
                    ->where('status', 'completed')
                    ->take(3)
                    ->map(fn ($export) => DesignExportController::present($project, $export))
                    ->values(),
            ]);

        return Inertia::render('Account/Designs/Index', ['projects' => $projects]);
    }

    public function store(Request $request, License $license): RedirectResponse
    {
        abort_unless(
            (int) $license->user_id === (int) $request->user()->id
                && $license->isActive()
                && $license->asset_id,
            403,
        );

        $license->load('asset.primaryPreviewFile');
        $width = max(1, (int) ($license->asset?->primaryPreviewFile?->width ?? 1920));
        $height = max(1, (int) ($license->asset?->primaryPreviewFile?->height ?? 1080));

        $project = DesignProject::create([
            'user_id' => $request->user()->id,
            'license_id' => $license->id,
            'asset_id' => $license->asset_id,
            'title' => ($license->asset?->title ?? 'Untitled design').' — Custom',
            'canvas_width' => $width,
            'canvas_height' => $height,
            'design_json' => ['version' => 1, 'objects' => []],
            'last_opened_at' => now(),
        ]);

        return redirect()->route('account.designs.edit', $project);
    }

    public function edit(Request $request, DesignProject $design): Response
    {
        $this->owned($request, $design);
        $design->load([
            'asset.primaryPreviewFile',
            'license',
            'uploads',
            'exports' => fn ($query) => $query
                ->where('status', 'completed')
                ->latest('completed_at')
                ->limit(12),
        ]);
        $design->forceFill(['last_opened_at' => now()])->save();
        $file = $design->asset?->primaryPreviewFile;

        return Inertia::render('Account/Designs/Edit', [
            'project' => [
                'uuid' => $design->uuid,
                'title' => $design->title,
                'canvas_width' => $design->canvas_width,
                'canvas_height' => $design->canvas_height,
                'design_json' => $design->design_json ?: ['version' => 1, 'objects' => []],
                'source_url' => $file ? route('assets.preview', [$design->asset, $file]) : null,
                'save_url' => route('account.designs.update', $design),
                'preview_upload_url' => route('account.designs.preview.store', $design),
                'upload_url' => route('account.designs.uploads.store', $design),
                'export_url' => route('account.designs.exports.store', $design),
                'uploads' => $design->uploads->map(fn ($upload) => [
                    'uuid' => $upload->uuid,
                    'name' => $upload->original_filename,
                    'url' => route('account.designs.uploads.show', [$design, $upload->uuid]),
                ]),
                'exports' => $design->exports
                    ->map(fn ($export) => DesignExportController::present($design, $export))
                    ->values(),
            ],
            'export_presets' => [
                ['name' => 'Social Square', 'width' => 1080, 'height' => 1080],
                ['name' => 'Social Portrait', 'width' => 1080, 'height' => 1350],
                ['name' => 'Story / Reel', 'width' => 1080, 'height' => 1920],
                ['name' => 'HD Landscape', 'width' => 1920, 'height' => 1080],
            ],
        ]);
    }

    public function update(Request $request, DesignProject $design): RedirectResponse
    {
        $this->owned($request, $design);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'canvas_width' => ['required', 'integer', 'min:320', 'max:12000'],
            'canvas_height' => ['required', 'integer', 'min:320', 'max:12000'],
            'design_json' => ['required', 'array'],
            'design_json.version' => ['required', 'integer'],
            'design_json.fabric' => ['required_if:design_json.version,2', 'array'],
            'design_json.fabric.objects' => ['required_if:design_json.version,2', 'array', 'max:200'],
            'design_json.objects' => ['required_if:design_json.version,1', 'array', 'max:200'],
        ]);

        $design->update($data);

        return back()->with('success', 'Design saved.');
    }

    public function storePreview(Request $request, DesignProject $design): JsonResponse
    {
        $this->owned($request, $design);
        $validated = $request->validate([
            'preview' => ['required', 'file', 'mimes:webp,png,jpg,jpeg', 'max:3072'],
        ]);

        if ($design->preview_path) {
            Storage::disk('local')->delete($design->preview_path);
        }

        $extension = strtolower($validated['preview']->getClientOriginalExtension() ?: 'webp');
        $path = $validated['preview']->storeAs(
            "designs/{$request->user()->id}/{$design->uuid}",
            "preview.{$extension}",
            'local',
        );
        $design->forceFill(['preview_path' => $path])->save();

        return response()->json([
            'preview_url' => route('account.designs.preview.show', $design).'?v='.$design->updated_at?->timestamp,
        ]);
    }

    public function preview(Request $request, DesignProject $design): BinaryFileResponse
    {
        $this->owned($request, $design);
        abort_unless($design->preview_path && Storage::disk('local')->exists($design->preview_path), 404);

        return response()->file(Storage::disk('local')->path($design->preview_path), [
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function destroy(Request $request, DesignProject $design): RedirectResponse
    {
        $this->owned($request, $design);

        foreach ($design->uploads as $upload) {
            Storage::disk($upload->disk)->delete($upload->path);
        }
        foreach ($design->exports as $export) {
            if ($export->disk && $export->path) {
                Storage::disk($export->disk)->delete($export->path);
            }
        }
        if ($design->preview_path) {
            Storage::disk('local')->delete($design->preview_path);
        }

        $design->delete();

        return redirect()->route('account.designs.index')->with('success', 'Design deleted.');
    }

    private function owned(Request $request, DesignProject $design): void
    {
        abort_unless((int) $design->user_id === (int) $request->user()->id, 403);
    }
}
