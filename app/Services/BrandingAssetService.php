<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class BrandingAssetService
{
    public const DIRECTORY = 'site/branding';

    public function store(UploadedFile $file, string $key): string
    {
        $this->delete($this->currentValue($key));

        $extension = strtolower($file->getClientOriginalExtension() ?: 'png');
        $filename = Str::slug($key).'-'.now()->format('YmdHis').'.'.$extension;
        $path = $file->storeAs(self::DIRECTORY, $filename, 'public');

        if (! $path) {
            throw new RuntimeException("Unable to store branding asset: {$key}");
        }

        return Storage::disk('public')->url($path);
    }

    public function generateAppIcons(UploadedFile $file): array
    {
        $contents = file_get_contents($file->getRealPath());
        $source = $contents !== false ? @imagecreatefromstring($contents) : false;

        if (! $source) {
            throw new RuntimeException('The app icon could not be processed. Upload a PNG, JPG, or WebP image.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);
        $sourceX = (int) (($width - $side) / 2);
        $sourceY = (int) (($height - $side) / 2);
        $generated = [];

        foreach ([16, 32, 180, 192, 512] as $size) {
            $canvas = imagecreatetruecolor($size, $size);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);
            imagecopyresampled($canvas, $source, 0, 0, $sourceX, $sourceY, $size, $size, $side, $side);

            $filename = match ($size) {
                180 => 'apple-touch-icon.png',
                192 => 'icon-192x192.png',
                512 => 'icon-512x512.png',
                default => "favicon-{$size}x{$size}.png",
            };
            $path = self::DIRECTORY.'/icons/'.$filename;
            ob_start(); imagepng($canvas, null, 9); $png = ob_get_clean();
            imagedestroy($canvas);
            Storage::disk('public')->put($path, $png);
            $generated[$size] = Storage::disk('public')->url($path);
        }

        imagedestroy($source);
        return $generated;
    }

    public function delete(?string $url): void
    {
        if (! $url || str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) return;
        $path = ltrim(Str::after($url, '/storage/'), '/');
        if ($path !== '' && Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
    }

    private function currentValue(string $key): ?string
    {
        return \App\Models\SiteSetting::query()->where('group_name', 'branding')->where('setting_key', $key)->value('setting_value');
    }
}
