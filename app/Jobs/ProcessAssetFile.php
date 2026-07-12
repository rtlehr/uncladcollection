<?php

namespace App\Jobs;

use App\Models\AssetFile;
use App\Services\AssetProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessAssetFile implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public int $assetFileId) {}

    public function handle(AssetProcessingService $processingService): void
    {
        $assetFile = AssetFile::withTrashed()->find($this->assetFileId);

        if (! $assetFile || $assetFile->trashed() || ! $assetFile->is_active) {
            return;
        }

        $processingService->process($assetFile);
    }
}
