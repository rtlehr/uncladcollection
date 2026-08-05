<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\DesignExport;
use App\Models\DesignProject;
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
        abort_unless($design->license?->isActive(), 403, 'The license for this design is no longer active.');

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:51200', 'mimetypes:image/jpeg,image/png,image/webp'],
            'width' => ['required', 'integer', 'min:320', 'max:12000'],
            'height' => ['required', 'integer', 'min:320', 'max:12000'],
            'format' => ['required', Rule::in(['jpg', 'jpeg', 'png', 'webp'])],
            'fit_mode' => ['required', Rule::in(['contain', 'cover'])],
            'preset_name' => ['nullable', 'string', 'max:80'],
        ]);

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
            'disk' => 'local',
            'path' => $path,
            'original_filename' => $filename,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'preset_name' => $validated['preset_name'] ?? null,
            'completed_at' => now(),
        ]);

        return response()->json($this->present($design, $export), 201);
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
            'created_at' => $export->completed_at?->diffForHumans() ?? $export->created_at?->diffForHumans(),
            'download_url' => route('account.designs.exports.download', [$design, $export]),
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

    private static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 1).' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 0).' KB';
        return $bytes.' B';
    }
}
