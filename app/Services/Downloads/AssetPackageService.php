<?php

namespace App\Services\Downloads;

use App\Models\AssetFile;
use App\Models\License;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class AssetPackageService
{
    /** @param Collection<int, AssetFile> $files */
    public function build(License $license, Collection $files): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP ZIP extension is required for package downloads.');
        }

        $directory = storage_path('app/private/download-packages');
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create the download package directory.');
        }

        $token = bin2hex(random_bytes(12));
        $path = $directory.'/license-'.$license->id.'-'.$token.'.zip';
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create the download package.');
        }

        foreach ($files as $file) {
            $stream = Storage::disk($file->disk)->readStream($file->path);
            if ($stream === false) {
                $zip->close();
                @unlink($path);
                throw new RuntimeException('One or more licensed files could not be read.');
            }

            $contents = stream_get_contents($stream);
            fclose($stream);
            $zip->addFromString($this->uniqueName($zip, $file->original_filename ?: $file->stored_filename), $contents ?: '');
        }

        $zip->close();

        $slug = str($license->asset?->slug ?? 'licensed-asset')->slug()->toString();

        return ['path' => $path, 'filename' => $slug.'-license-'.$license->license_key.'.zip'];
    }

    private function uniqueName(ZipArchive $zip, string $name): string
    {
        $safe = basename($name);
        if ($zip->locateName($safe) === false) {
            return $safe;
        }

        $extension = pathinfo($safe, PATHINFO_EXTENSION);
        $base = pathinfo($safe, PATHINFO_FILENAME);
        $counter = 2;
        do {
            $candidate = $base.'-'.$counter.($extension !== '' ? '.'.$extension : '');
            $counter++;
        } while ($zip->locateName($candidate) !== false);

        return $candidate;
    }
}
