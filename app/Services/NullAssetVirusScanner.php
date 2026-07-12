<?php

namespace App\Services;

use App\Contracts\AssetVirusScanner;
use App\Data\AssetVirusScanResult;
use App\Models\AssetFile;

class NullAssetVirusScanner implements AssetVirusScanner
{
    public function scan(AssetFile $assetFile): AssetVirusScanResult
    {
        return AssetVirusScanResult::notRequired([
            'scanner' => 'none',
        ]);
    }
}
