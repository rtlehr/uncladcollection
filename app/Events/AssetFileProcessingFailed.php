<?php

namespace App\Events;

use App\Models\AssetFile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetFileProcessingFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AssetFile $assetFile,
        public string $message,
    ) {}
}
