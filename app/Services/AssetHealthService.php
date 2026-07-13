<?php

namespace App\Services;

use App\Models\Asset;

class AssetHealthService
{
    public function summarize(Asset $asset): array
    {
        $asset->loadMissing(['activeFiles', 'offerings']);

        $checks = [
            ['key' => 'title', 'label' => 'Title added', 'complete' => filled($asset->title)],
            ['key' => 'description', 'label' => 'Description added', 'complete' => filled($asset->description)],
            ['key' => 'creator', 'label' => 'Creator identified', 'complete' => filled($asset->photographer)],
            ['key' => 'files', 'label' => 'Files uploaded', 'complete' => $asset->activeFiles->isNotEmpty()],
            ['key' => 'preview', 'label' => 'Primary preview selected', 'complete' => filled($asset->primary_preview_file_id)],
            ['key' => 'offerings', 'label' => 'Active license offering configured', 'complete' => $asset->offerings->contains('is_active', true)],
        ];

        if ($asset->activeFiles->contains(fn ($file) => $file->media_type->value === 'video')) {
            $checks[] = ['key' => 'poster', 'label' => 'Video poster selected', 'complete' => filled($asset->poster_file_id)];
        }

        $completed = collect($checks)->where('complete', true)->count();
        $score = count($checks) > 0 ? (int) round(($completed / count($checks)) * 100) : 0;

        return [
            'score' => $score,
            'status' => match (true) {
                $score >= 90 => 'ready',
                $score >= 65 => 'needs_review',
                default => 'needs_attention',
            },
            'checks' => $checks,
        ];
    }
}
