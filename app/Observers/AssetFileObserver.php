<?php

namespace App\Observers;

use App\Enums\AssetFileProcessingStatus;
use App\Enums\AssetFileScanStatus;
use App\Models\AssetFile;
use Illuminate\Support\Str;

class AssetFileObserver
{
    public function creating(AssetFile $assetFile): void
    {
        $assetFile->uuid ??= (string) Str::uuid();
        $assetFile->extension = strtolower(ltrim((string) $assetFile->extension, '.'));
        $assetFile->original_filename = basename($assetFile->original_filename);
        $assetFile->stored_filename = basename($assetFile->stored_filename);
        $assetFile->processing_status ??= AssetFileProcessingStatus::Pending;
        $assetFile->virus_scan_status ??= AssetFileScanStatus::Pending;
    }
}
