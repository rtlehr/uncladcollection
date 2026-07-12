<?php

namespace App\Data;

use App\Enums\AssetFileScanStatus;

final readonly class AssetVirusScanResult
{
    public function __construct(
        public AssetFileScanStatus $status,
        public ?string $message = null,
        public array $metadata = [],
    ) {}

    public static function clean(array $metadata = []): self
    {
        return new self(AssetFileScanStatus::Clean, metadata: $metadata);
    }

    public static function rejected(string $message, array $metadata = []): self
    {
        return new self(AssetFileScanStatus::Rejected, $message, $metadata);
    }

    public static function failed(string $message, array $metadata = []): self
    {
        return new self(AssetFileScanStatus::Failed, $message, $metadata);
    }

    public static function notRequired(array $metadata = []): self
    {
        return new self(AssetFileScanStatus::NotRequired, metadata: $metadata);
    }
}
