<?php

namespace App\Services;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

class AssetColorAnalysisService
{
    public function analyze(string $absolutePath): array
    {
        try {
            $image = (new ImageManager(new Driver()))
                ->decode($absolutePath)
                ->scaleDown(width: 120, height: 120);
            $colors = [];

            for ($y = 0; $y < $image->height(); $y += 6) {
                for ($x = 0; $x < $image->width(); $x += 6) {
                    $hex = strtoupper($image->pickColor($x, $y)->toHex());
                    $bucket = substr($hex, 0, 4).'00';
                    $colors[$bucket] = ($colors[$bucket] ?? 0) + 1;
                }
            }

            arsort($colors);

            return [
                'dominant_colors' => collect(array_keys($colors))->take(6)->values()->all(),
                'sample_width' => $image->width(),
                'sample_height' => $image->height(),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'dominant_colors' => [],
                'analysis_error' => 'Local color analysis was unavailable.',
            ];
        }
    }
}
