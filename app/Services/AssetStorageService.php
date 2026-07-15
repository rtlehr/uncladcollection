<?php

namespace App\Services;

use App\Enums\AssetFileRole;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Support\Uploads\UploadStreamResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class AssetStorageService
{
    public function __construct(
        private readonly UploadStreamResolver $streams,
    ) {
    }

    public function disk(): string
    {
        return (string) config('asset-media.private_disk', 'asset-files');
    }

    public function directory(Asset $asset, AssetFileRole $role): string
    {
        $date = $asset->created_at ?? now();

        return sprintf(
            'assets/%s/%s/%s/%s',
            $date->format('Y'),
            $date->format('m'),
            $asset->uuid,
            $this->roleDirectory($role),
        );
    }

    public function store(
        Asset $asset,
        UploadedFile $file,
        AssetFileRole $role,
    ): array {
        $disk = $this->disk();
        $directory = $this->directory($asset, $role);
        $storedFilename = $this->randomFilename($file);
        $path = trim($directory.'/'.$storedFilename, '/');

        try {
            $checksum = $this->checksum($file);
            $stored = $this->withReadableStream(
                $file,
                fn ($stream): bool => Storage::disk($disk)->put(
                    $path,
                    $stream,
                    ['visibility' => 'private'],
                ),
            );
        } catch (Throwable $exception) {
            $this->logFailure(
                operation: 'store',
                file: $file,
                exception: $exception,
                context: [
                    'asset_id' => $asset->id,
                    'asset_uuid' => $asset->uuid,
                    'role' => $role->value,
                    'disk' => $disk,
                    'path' => $path,
                ],
            );

            throw $exception;
        }

        if (! $stored) {
            $exception = new RuntimeException(
                'The uploaded file could not be stored.',
            );

            $this->logFailure(
                operation: 'store',
                file: $file,
                exception: $exception,
                context: [
                    'asset_id' => $asset->id,
                    'asset_uuid' => $asset->uuid,
                    'role' => $role->value,
                    'disk' => $disk,
                    'path' => $path,
                ],
            );

            throw $exception;
        }

        return [
            'disk' => $disk,
            'directory' => $directory,
            'stored_filename' => $storedFilename,
            'path' => $path,
            'checksum_sha256' => $checksum,
        ];
    }

    public function delete(AssetFile $assetFile): bool
    {
        return $this->deleteStoredPath(
            $assetFile->disk,
            $assetFile->path,
        );
    }

    public function deleteStoredPath(string $disk, string $path): bool
    {
        if (! Storage::disk($disk)->exists($path)) {
            return true;
        }

        return Storage::disk($disk)->delete($path);
    }

    public function randomFilename(UploadedFile $file): string
    {
        $extension = strtolower(
            $file->getClientOriginalExtension()
            ?: $file->guessExtension()
            ?: '',
        );

        if ($extension === '') {
            throw new InvalidArgumentException(
                'The uploaded file must have an approved extension.',
            );
        }

        return Str::ulid()->toBase32().'.'.$extension;
    }

    public function checksum(UploadedFile $file): string
    {
        try {
            return $this->withReadableStream(
                $file,
                function ($stream): string {
                    $context = hash_init('sha256');
                    $result = hash_update_stream($context, $stream);

                    if ($result === false) {
                        throw new RuntimeException(
                            'The uploaded file checksum could not be calculated.',
                        );
                    }

                    return hash_final($context);
                },
            );
        } catch (Throwable $exception) {
            $this->logFailure(
                operation: 'checksum',
                file: $file,
                exception: $exception,
            );

            throw $exception;
        }
    }

    /**
     * @template T
     *
     * @param callable(resource): T $callback
     * @return T
     */
    private function withReadableStream(
        UploadedFile $file,
        callable $callback,
    ): mixed {
        $stream = $this->streams->open($file);

        try {
            return $callback($stream);
        } finally {
            fclose($stream);
        }
    }

    private function logFailure(
        string $operation,
        UploadedFile $file,
        Throwable $exception,
        array $context = [],
    ): void {
        Log::error('Asset upload processing failed.', [
            ...$context,
            'operation' => $operation,
            'filename' => $file->getClientOriginalName(),
            'client_extension' => $file->getClientOriginalExtension(),
            'client_mime_type' => $file->getClientMimeType(),
            'reported_size_bytes' => $file->getSize() ?: null,
            'real_path_available' => $file->getRealPath() !== false,
            'pathname' => $file->getPathname(),
            'uploaded_file_class' => $file::class,
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
        ]);
    }

    private function roleDirectory(AssetFileRole $role): string
    {
        return match ($role) {
            AssetFileRole::Preview,
            AssetFileRole::Thumbnail,
            AssetFileRole::Icon,
            AssetFileRole::Poster => 'preview',
            AssetFileRole::Primary,
            AssetFileRole::HighResolution,
            AssetFileRole::Print => 'image',
            AssetFileRole::Vector => 'vector',
            AssetFileRole::Video => 'video',
            AssetFileRole::Source => 'source',
            AssetFileRole::Bundle => 'bundle',
            AssetFileRole::Supplemental => 'supplemental',
        };
    }
}
