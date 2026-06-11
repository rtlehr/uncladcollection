<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Alignment;

class ImageProcessingService
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function processUploadedImage(UploadedFile $uploadedFile, Image $image): array
    {
        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        $baseFilename = Str::slug(pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME));

        if ($baseFilename === '') {
            $baseFilename = 'image';
        }

        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $extension = 'jpg';
        }

        $filename = "{$baseFilename}.{$extension}";
        $baseFolder = $this->imageBaseFolder($image);

        $originalPath = "{$baseFolder}/original/{$filename}";
        $highResPath = "{$baseFolder}/high-res/{$filename}";
        $thumbnailPath = "{$baseFolder}/thumbnail/{$filename}";
        $iconPath = "{$baseFolder}/icon/{$filename}";

        Storage::disk('public')->put(
            $originalPath,
            file_get_contents($uploadedFile->getRealPath())
        );

        $this->createHighRes($uploadedFile, $highResPath);
        $this->createThumbnail($uploadedFile, $thumbnailPath);
        $this->createIcon($uploadedFile, $iconPath);

        return [
            'original_path' => $originalPath,
            'high_res_path' => $highResPath,
            'thumbnail_path' => $thumbnailPath,
            'icon_path' => $iconPath,
        ];
    }

    private function createHighRes(UploadedFile $uploadedFile, string $path): void
    {
        $image = $this->manager->decode($uploadedFile->getRealPath());

        $image->scaleDown(width: 2000);

        Storage::disk('public')->put($path, $this->encodeImage($image));
    }

    private function createThumbnail(UploadedFile $uploadedFile, string $path): void
    {
        $image = $this->manager->decode($uploadedFile->getRealPath());

        $image->scaleDown(width: 600);

        $this->addVisualWatermark($image);

        Storage::disk('public')->put($path, $this->encodeImage($image));
    }

    private function createIcon(UploadedFile $uploadedFile, string $path): void
    {
        $image = $this->manager->decode($uploadedFile->getRealPath());

        $image->cover(300, 300);

        $this->addVisualWatermark($image);

        Storage::disk('public')->put($path, $this->encodeImage($image));
    }

    private function addVisualWatermark($image): void
    {
        $height = $image->height();
        $width = $image->width();

        $text = 'UNCLADCOLLECTION.COM';
        $fontPath = 'C:\Windows\Fonts\arial.ttf';

        // Start large, then shrink until the full text fits inside the image.
        $fontSize = max(14, (int) round($width * 0.08));
        $maxFontSize = $fontSize;

        // Approximate text width: characters * font size * 0.6
        while (($this->estimatedTextWidth($text, $fontSize) > ($width * 0.9)) && $fontSize > 10) {
            $fontSize -= 2;
        }

        $image->text($text, (int) ($width / 2), (int) ($height / 2), function ($font) use ($fontPath, $fontSize) {
            $font->filepath($fontPath);
            $font->size($fontSize);

            // 50% transparent white
            $font->color('rgba(255,255,255,0.75)');

            // Rotate watermark
            $font->angle(-25);

            $font->align(
                \Intervention\Image\Alignment::CENTER,
                \Intervention\Image\Alignment::CENTER
            );
        });
    }

    private function estimatedTextWidth(string $text, int $fontSize): float
    {
        return strlen($text) * $fontSize * 0.62;
    }

    private function encodeImage($image): string
    {
        return (string) $image->encode();
    }

    private function imageBaseFolder(Image $image): string
    {
        $collection = $image->collection;

        if (! $collection && $image->collection_id) {
            $collection = Collection::find($image->collection_id);
        }

        $collectionFolder = $collection
            ? "{$collection->id}-{$collection->slug}"
            : 'unassigned';

        return "images/{$collectionFolder}/{$image->id}";
    }
}