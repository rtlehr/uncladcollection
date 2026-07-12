<?php

namespace App\Services;

use App\Contracts\AssetVirusScanner;
use App\Enums\AssetFileProcessingStatus;
use App\Enums\AssetFileScanStatus;
use App\Events\AssetFileProcessingCompleted;
use App\Events\AssetFileProcessingFailed;
use App\Models\AssetFile;
use Illuminate\Support\Arr;
use Throwable;

class AssetProcessingService
{
    public function __construct(
        private readonly AssetVirusScanner $virusScanner,
        private readonly AssetMetadataService $metadataService,
    ) {}

    public function process(AssetFile $assetFile): AssetFile
    {
        $assetFile->refresh();

        if (! $assetFile->exists()) {
            return $this->fail($assetFile, 'The stored asset file does not exist.');
        }

        $assetFile->update([
            'processing_status' => AssetFileProcessingStatus::Processing,
        ]);

        try {
            $scan = $this->virusScanner->scan($assetFile);
            $metadata = array_merge($assetFile->metadata ?? [], [
                'virus_scan' => array_filter([
                    'status' => $scan->status->value,
                    'message' => $scan->message,
                    'metadata' => $scan->metadata,
                ], fn ($value) => $value !== null && $value !== []),
            ]);

            $assetFile->update([
                'virus_scan_status' => $scan->status,
                'metadata' => $metadata,
            ]);

            if (in_array($scan->status, [AssetFileScanStatus::Rejected, AssetFileScanStatus::Failed], true)) {
                return $this->fail($assetFile, $scan->message ?: 'The asset file failed virus scanning.');
            }

            $detected = $this->metadataService->extract($assetFile);
            $assetFile->update([
                'width' => Arr::get($detected, 'width', $assetFile->width),
                'height' => Arr::get($detected, 'height', $assetFile->height),
                'metadata' => array_merge($metadata, ['technical' => $detected]),
                'processing_status' => AssetFileProcessingStatus::Ready,
            ]);

            event(new AssetFileProcessingCompleted($assetFile->fresh()));

            return $assetFile->fresh();
        } catch (Throwable $exception) {
            report($exception);

            return $this->fail($assetFile, $exception->getMessage());
        }
    }

    private function fail(AssetFile $assetFile, string $message): AssetFile
    {
        $metadata = array_merge($assetFile->metadata ?? [], [
            'processing_error' => $message,
            'processing_failed_at' => now()->toIso8601String(),
        ]);

        $assetFile->update([
            'processing_status' => AssetFileProcessingStatus::Failed,
            'metadata' => $metadata,
        ]);

        event(new AssetFileProcessingFailed($assetFile->fresh(), $message));

        return $assetFile->fresh();
    }
}
