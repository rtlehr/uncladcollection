<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class AssetZipInspectionService
{
    public function inspect(UploadedFile $file): array
    {
        if (! class_exists(ZipArchive::class)) {
            if (config('asset-media.zip.require_extension', false)) {
                throw ValidationException::withMessages([
                    'files' => 'ZIP inspection is unavailable because the PHP zip extension is not enabled.',
                ]);
            }

            return ['inspection' => 'skipped', 'reason' => 'zip_extension_unavailable'];
        }

        $zip = new ZipArchive();
        $opened = $zip->open($file->getRealPath());

        if ($opened !== true) {
            throw ValidationException::withMessages(['files' => 'The ZIP archive could not be opened.']);
        }

        try {
            $maxEntries = (int) config('asset-media.zip.max_entries', 5000);
            $maxExpanded = (int) config('asset-media.zip.max_expanded_bytes', 2147483648);
            $maxRatio = (float) config('asset-media.zip.max_compression_ratio', 100);
            $allowNested = (bool) config('asset-media.zip.allow_nested_archives', false);
            $blocked = array_map('strtolower', (array) config('asset-media.blocked_extensions', []));

            if ($zip->numFiles > $maxEntries) {
                throw ValidationException::withMessages(['files' => 'The ZIP archive contains too many entries.']);
            }

            $expandedBytes = 0;
            $compressedBytes = 0;

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                $name = str_replace('\\', '/', (string) ($stat['name'] ?? ''));

                if ($name === '' || str_starts_with($name, '/') || preg_match('/(^|\/)\.\.(\/|$)/', $name)) {
                    throw ValidationException::withMessages(['files' => 'The ZIP archive contains an unsafe path.']);
                }

                if (preg_match('/^[A-Za-z]:\//', $name)) {
                    throw ValidationException::withMessages(['files' => 'The ZIP archive contains an absolute Windows path.']);
                }

                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                if ($extension !== '' && in_array($extension, $blocked, true)) {
                    throw ValidationException::withMessages(['files' => "The ZIP archive contains a blocked .{$extension} file."]);
                }

                if (! $allowNested && in_array($extension, ['zip', 'rar', '7z', 'tar', 'gz'], true)) {
                    throw ValidationException::withMessages(['files' => 'Nested archives are not allowed.']);
                }

                $expandedBytes += (int) ($stat['size'] ?? 0);
                $compressedBytes += (int) ($stat['comp_size'] ?? 0);

                if ($expandedBytes > $maxExpanded) {
                    throw ValidationException::withMessages(['files' => 'The ZIP archive expands beyond the configured safety limit.']);
                }
            }

            if ($compressedBytes > 0 && ($expandedBytes / $compressedBytes) > $maxRatio) {
                throw ValidationException::withMessages(['files' => 'The ZIP archive has a suspicious compression ratio.']);
            }

            return [
                'inspection' => 'passed',
                'entries' => $zip->numFiles,
                'expanded_bytes' => $expandedBytes,
                'compressed_bytes' => $compressedBytes,
            ];
        } finally {
            $zip->close();
        }
    }
}
