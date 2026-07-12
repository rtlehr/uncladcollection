<?php

namespace App\Events;

use App\Models\AssetFile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetFileAdded
{
    use Dispatchable, SerializesModels;

    public function __construct(public AssetFile $assetFile) {}
}
