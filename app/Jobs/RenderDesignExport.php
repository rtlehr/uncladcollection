<?php

namespace App\Jobs;

use App\Models\DesignExport;
use App\Services\DesignStudio\ServerDesignRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RenderDesignExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct(public int $designExportId, public string $overlayPath) {}

    public function handle(ServerDesignRenderer $renderer): void
    {
        $export = DesignExport::query()->with(['project.asset.activeFiles', 'project.uploads', 'project.license'])->find($this->designExportId);
        if (! $export || $export->status === 'completed') {
            return;
        }

        $export->forceFill([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
        ])->save();

        try {
            $renderer->render($export, $this->overlayPath);
        } catch (Throwable $exception) {
            report($exception);
            $export->forceFill([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
            ])->save();
            Storage::disk('local')->delete($this->overlayPath);
        }
    }
}
