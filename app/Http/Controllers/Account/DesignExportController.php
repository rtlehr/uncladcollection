<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Jobs\RenderDesignExport;
use App\Models\DesignExport;
use App\Models\DesignProject;
use App\Models\License;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DesignExportController extends Controller
{
    public function store(Request $request, DesignProject $design): JsonResponse
    {
        $this->authorizeProject($request, $design);
        $this->assertActiveLicense($design);

        $maxWidth = (int) config('design-studio.max_browser_width', 12000);
        $maxHeight = (int) config('design-studio.max_browser_height', 12000);
        $maxPixels = (int) config('design-studio.max_browser_pixels', 40000000);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:51200', 'mimetypes:image/jpeg,image/png,image/webp'],
            'width' => ['required', 'integer', 'min:320', 'max:'.$maxWidth],
            'height' => ['required', 'integer', 'min:320', 'max:'.$maxHeight],
            'format' => ['required', Rule::in(['jpg', 'jpeg', 'png', 'webp'])],
            'fit_mode' => ['required', Rule::in(['contain', 'cover'])],
            'preset_name' => ['nullable', 'string', 'max:80'],
            'design_json' => ['required', 'string'],
        ]);

        $designJson = json_decode($validated['design_json'], true);
        abort_unless(is_array($designJson) && isset($designJson['fabric']['objects']) && is_array($designJson['fabric']['objects']), 422, 'The design data is invalid.');
        abort_if(count($designJson['fabric']['objects']) > (int) config('design-studio.max_layer_count', 200), 422, 'The design contains too many elements.');
        $this->assertReferencedLibraryLicenses($request, $designJson);
        $design->forceFill(['design_json' => $designJson])->save();

        abort_if(((int) $validated['width'] * (int) $validated['height']) > $maxPixels, 422, 'The requested browser export exceeds the allowed pixel limit.');

        $file = $request->file('file');
        $size = @getimagesize($file->getRealPath());
        abort_unless(is_array($size), 422, 'The exported file is not a valid image.');
        abort_unless((int) $size[0] === (int) $validated['width'] && (int) $size[1] === (int) $validated['height'], 422, 'The exported dimensions do not match the requested dimensions.');

        $format = $validated['format'] === 'jpeg' ? 'jpg' : $validated['format'];
        $extension = $format;
        $filename = sprintf('%s-%dx%d-%s.%s', $design->uuid, $validated['width'], $validated['height'], now()->format('Ymd-His'), $extension);
        $path = $file->storeAs("designs/{$request->user()->id}/{$design->uuid}/exports", $filename, 'local');

        $export = $design->exports()->create([
            'user_id' => $request->user()->id,
            'width' => $validated['width'],
            'height' => $validated['height'],
            'format' => $format,
            'fit_mode' => $validated['fit_mode'],
            'status' => 'completed',
            'render_engine' => 'browser-fabric',
            'disk' => 'local',
            'path' => $path,
            'original_filename' => $filename,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'preset_name' => $validated['preset_name'] ?? null,
            'completed_at' => now(),
        ]);

        return response()->json(self::present($design, $export), 201);
    }

    public function render(Request $request, DesignProject $design): JsonResponse
    {
        $this->authorizeProject($request, $design);
        $this->assertActiveLicense($design);

        $maxQueued = max(1, (int) config('design-studio.max_queued_renders_per_user', 5));
        $activeQueued = DesignExport::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        abort_if($activeQueued >= $maxQueued, 429, 'You already have the maximum number of queued server renders in progress. Please wait for one to finish or fail before queueing another.');

        $validated = $request->validate([
            'width' => ['required', 'integer', 'min:320', 'max:'.config('design-studio.max_server_width', 12000)],
            'height' => ['required', 'integer', 'min:320', 'max:'.config('design-studio.max_server_height', 12000)],
            'format' => ['required', Rule::in(['jpg', 'jpeg', 'png', 'webp'])],
            'fit_mode' => ['required', Rule::in(['contain', 'cover'])],
            'preset_name' => ['nullable', 'string', 'max:80'],
            'overlay' => ['required', 'file', 'max:51200', 'mimetypes:image/png'],
            'design_json' => ['required', 'string'],
        ]);

        abort_if(((int) $validated['width'] * (int) $validated['height']) > (int) config('design-studio.max_server_pixels', 80000000), 422, 'The requested export exceeds the server rendering pixel limit.');

        $designJson = json_decode($validated['design_json'], true);
        abort_unless(is_array($designJson) && isset($designJson['fabric']['objects']) && is_array($designJson['fabric']['objects']), 422, 'The design data is invalid.');
        abort_if(count($designJson['fabric']['objects']) > (int) config('design-studio.max_layer_count', 200), 422, 'The design contains too many elements.');
        $this->assertReferencedLibraryLicenses($request, $designJson);
        $design->forceFill(['design_json' => $designJson])->save();

        $overlay = $request->file('overlay');
        $overlaySize = @getimagesize($overlay->getRealPath());
        abort_unless(is_array($overlaySize), 422, 'The render overlay is not a valid PNG image.');
        abort_unless((int) $overlaySize[0] === (int) $validated['width'] && (int) $overlaySize[1] === (int) $validated['height'], 422, 'The render overlay dimensions do not match the requested export.');
        $overlayPath = $overlay->storeAs(
            "designs/{$request->user()->id}/{$design->uuid}/render-overlays",
            'overlay-'.now()->format('Ymd-His-u').'.png',
            'local'
        );

        $format = $validated['format'] === 'jpeg' ? 'jpg' : $validated['format'];
        $export = $design->exports()->create([
            'user_id' => $request->user()->id,
            'width' => $validated['width'],
            'height' => $validated['height'],
            'format' => $format,
            'fit_mode' => $validated['fit_mode'],
            'preset_name' => $validated['preset_name'] ?? null,
            'status' => 'pending',
            'render_engine' => 'server-gd',
            'queued_at' => now(),
        ]);

        RenderDesignExport::dispatch($export->id, $overlayPath);

        return response()->json(self::present($design, $export), 202);
    }

    public function status(Request $request, DesignProject $design, DesignExport $export): JsonResponse
    {
        $this->authorizeExport($request, $design, $export);
        $export->refresh();

        return response()->json(self::present($design, $export));
    }

    public function download(Request $request, DesignProject $design, DesignExport $export): StreamedResponse
    {
        $this->authorizeExport($request, $design, $export);
        abort_unless($export->status === 'completed' && $export->disk && $export->path, 404);
        abort_unless(Storage::disk($export->disk)->exists($export->path), 404);

        return Storage::disk($export->disk)->download($export->path, $export->original_filename ?: basename($export->path), [
            'Content-Type' => $export->mime_type ?: 'application/octet-stream',
        ]);
    }

    public function destroy(Request $request, DesignProject $design, DesignExport $export): RedirectResponse
    {
        $this->authorizeExport($request, $design, $export);
        if ($export->disk && $export->path) {
            Storage::disk($export->disk)->delete($export->path);
        }
        $export->delete();

        return back()->with('success', 'Export deleted.');
    }

    /** @return array<string, mixed> */
    public static function present(DesignProject $design, DesignExport $export): array
    {
        return [
            'uuid' => $export->uuid,
            'width' => $export->width,
            'height' => $export->height,
            'format' => strtoupper($export->format),
            'fit_mode' => $export->fit_mode,
            'preset_name' => $export->preset_name,
            'size' => $export->size_bytes ? self::formatBytes($export->size_bytes) : null,
            'status' => $export->status,
            'render_engine' => $export->render_engine,
            'error_message' => $export->error_message,
            'retryable' => $export->status === 'failed' && ($export->render_engine === 'server-gd'),
            'created_at' => $export->completed_at?->diffForHumans() ?? $export->created_at?->diffForHumans(),
            'status_url' => route('account.designs.exports.status', [$design, $export]),
            'download_url' => $export->status === 'completed' ? route('account.designs.exports.download', [$design, $export]) : null,
            'delete_url' => route('account.designs.exports.destroy', [$design, $export]),
        ];
    }

    private function authorizeProject(Request $request, DesignProject $design): void
    {
        abort_unless((int) $design->user_id === (int) $request->user()->id, 403);
        $design->loadMissing('license');
    }

    private function authorizeExport(Request $request, DesignProject $design, DesignExport $export): void
    {
        $this->authorizeProject($request, $design);
        abort_unless((int) $export->design_project_id === (int) $design->id && (int) $export->user_id === (int) $request->user()->id, 403);
    }

    private function assertActiveLicense(DesignProject $design): void
    {
        if ($design->license_id === null) {
            return;
        }

        abort_unless($design->license?->isActive(), 403, 'The license for this design is no longer active.');
    }


    /** @param array<string, mixed> $designJson */
    private function assertReferencedLibraryLicenses(Request $request, array $designJson): void
    {
        $libraryObjects = collect($designJson['fabric']['objects'] ?? [])
            ->filter(fn ($object) => is_array($object) && ($object['sourceType'] ?? null) === 'licensed_asset');

        $references = $libraryObjects
            ->map(fn (array $object) => [
                'license_id' => (int) ($object['sourceLicenseId'] ?? 0),
                'asset_id' => (int) ($object['sourceAssetId'] ?? 0),
            ]);

        abort_if(
            $references->contains(fn (array $reference) => $reference['license_id'] <= 0 || $reference['asset_id'] <= 0),
            422,
            'A UC Library layer is missing its license reference.',
        );

        $references = $references
            ->unique(fn (array $reference) => $reference['license_id'].'-'.$reference['asset_id'])
            ->values();

        if ($references->isEmpty()) {
            return;
        }

        $licenses = License::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('id', $references->pluck('license_id'))
            ->get()
            ->keyBy('id');

        foreach ($references as $reference) {
            $license = $licenses->get($reference['license_id']);
            abort_unless(
                $license && $license->isActive() && (int) $license->asset_id === (int) $reference['asset_id'],
                403,
                'One or more UC Library images in this design no longer have an active matching license.',
            );
        }
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0).' KB';
        }

        return $bytes.' B';
    }
}
