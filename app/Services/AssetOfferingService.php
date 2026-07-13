<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetOffering;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AssetOfferingService
{
    public function __construct(private readonly AssetPricingService $pricingService) {}
    public function saveMany(Asset $asset, array $offerings): void
    {
        DB::transaction(function () use ($asset, $offerings): void {
            $keep = [];
            foreach ($offerings as $index => $data) {
                $offering = $asset->offerings()->updateOrCreate(
                    ['license_type_id' => $data['license_type_id']],
                    [
                        'name' => $data['name'],
                        'description' => Arr::get($data, 'description'),
                        'price_cents' => $data['price_cents'],
                        'currency' => strtoupper($data['currency'] ?? 'USD'),
                        'download_limit' => Arr::get($data, 'download_limit'),
                        'expires_after_days' => Arr::get($data, 'expires_after_days'),
                        'include_all_active_files' => (bool) ($data['include_all_active_files'] ?? false),
                        'is_active' => (bool) ($data['is_active'] ?? true),
                        'sort_order' => ($index + 1) * 10,
                    ]
                );
                $keep[] = $offering->id;

                $fileIds = collect($data['file_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
                $validIds = $asset->activeFiles()->whereIn('id', $fileIds)->pluck('id');
                $offering->files()->sync($validIds->mapWithKeys(fn ($id, $i) => [$id => ['sort_order' => ($i + 1) * 10]])->all());
            }

            $asset->offerings()->whereNotIn('id', $keep)->delete();

            $pricingTiers = [];
            foreach ($offerings as $data) {
                $offering = $asset->offerings()->where('license_type_id', $data['license_type_id'])->first();
                foreach ($data['pricing_tiers'] ?? [] as $tier) {
                    $pricingTiers[] = [...$tier, 'asset_offering_id' => $offering?->id];
                }
            }
            $this->pricingService->saveMany($asset, $pricingTiers);
        });
    }

    public function snapshot(AssetOffering $offering): array
    {
        return $offering->includedFiles()->map(fn ($file) => [
            'asset_file_id' => $file->id,
            'uuid' => $file->uuid,
            'original_filename' => $file->original_filename,
            'role' => $file->role->value,
            'media_type' => $file->media_type->value,
            'extension' => $file->extension,
            'mime_type' => $file->mime_type,
            'size_bytes' => $file->size_bytes,
            'checksum_sha256' => $file->checksum_sha256,
        ])->values()->all();
    }
}
