<?php

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Services\AssetHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('scores an incomplete asset and identifies missing readiness checks', function (): void {
    $asset = Asset::query()->create([
        'uuid' => (string) Str::uuid(),
        'title' => 'Incomplete Asset',
        'slug' => 'incomplete-asset',
        'description' => null,
        'asset_type' => AssetType::Image,
        'status' => AssetStatus::Draft,
        'photographer' => null,
        'sort_order' => 0,
        'is_active' => true,
        'is_featured' => false,
        'is_ai_generated' => false,
    ]);

    $health = app(AssetHealthService::class)->summarize($asset);

    expect($health['score'])->toBeLessThan(65)
        ->and($health['status'])->toBe('needs_attention')
        ->and(collect($health['checks'])->firstWhere('key', 'preview')['complete'])->toBeFalse()
        ->and(collect($health['checks'])->firstWhere('key', 'offerings')['complete'])->toBeFalse();
});
