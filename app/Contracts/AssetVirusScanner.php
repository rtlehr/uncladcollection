<?php

namespace App\Contracts;

use App\Data\AssetVirusScanResult;
use App\Models\AssetFile;

interface AssetVirusScanner
{
    public function scan(AssetFile $assetFile): AssetVirusScanResult;
}
