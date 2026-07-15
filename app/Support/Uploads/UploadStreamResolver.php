<?php

namespace App\Support\Uploads;

use Illuminate\Http\UploadedFile;
use Illuminate\Http\Testing\File as TestingFile;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class UploadStreamResolver
{
    /**
     * Open an independent readable stream for an uploaded file.
     *
     * The returned resource is owned by the caller and must be closed.
     *
     * @return resource
     */
    public function open(UploadedFile $file)
    {
        if ($file instanceof TestingFile) {
            $stream = $this->copyLaravelTestingStream($file);

            if (is_resource($stream)) {
                return $stream;
            }
        }

        foreach ($this->candidatePaths($file) as $path) {
            $stream = $this->openPath($path);

            if (is_resource($stream)) {
                return $stream;
            }
        }

        $stream = $this->copyUploadedFileContent($file);

        if (is_resource($stream)) {
            return $stream;
        }

        throw new InvalidArgumentException(
            'The uploaded file could not be read.',
        );
    }

    /**
     * Laravel's testing File owns a tmpfile() resource. Its reported size may
     * intentionally differ from its physical byte length, and its temporary
     * path is not consistently reopenable on every operating system.
     *
     * Copy the framework-owned resource into a new php://temp stream instead
     * of closing, moving, or otherwise taking ownership of the original.
     *
     * @return resource|null
     */
    private function copyLaravelTestingStream(TestingFile $file)
    {
        if (! property_exists($file, 'tempFile')) {
            return null;
        }

        /** @var mixed $source */
        $source = $file->tempFile;

        if (! is_resource($source)) {
            return null;
        }

        return $this->duplicateResource($source);
    }

    /**
     * @return resource|null
     */
    private function copyUploadedFileContent(UploadedFile $file)
    {
        try {
            $contents = $file->getContent();
        } catch (Throwable) {
            return null;
        }

        if (! is_string($contents)) {
            return null;
        }

        $stream = $this->temporaryStream();

        if ($contents !== '' && fwrite($stream, $contents) === false) {
            fclose($stream);

            throw new RuntimeException(
                'The uploaded file could not be copied to a temporary stream.',
            );
        }

        rewind($stream);

        return $stream;
    }

    /**
     * @param resource $source
     * @return resource
     */
    private function duplicateResource($source)
    {
        $metadata = stream_get_meta_data($source);
        $seekable = (bool) ($metadata['seekable'] ?? false);
        $position = $seekable ? ftell($source) : false;

        if ($seekable) {
            rewind($source);
        }

        $destination = $this->temporaryStream();
        $copied = stream_copy_to_stream($source, $destination);

        if ($seekable && $position !== false) {
            fseek($source, $position);
        }

        if ($copied === false) {
            fclose($destination);

            throw new RuntimeException(
                'The uploaded file stream could not be copied.',
            );
        }

        rewind($destination);

        return $destination;
    }

    /**
     * @return resource|null
     */
    private function openPath(string $path)
    {
        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $stream = @fopen($path, 'rb');

        return is_resource($stream) ? $stream : null;
    }

    /**
     * @return list<string>
     */
    private function candidatePaths(UploadedFile $file): array
    {
        $paths = [];

        $realPath = $file->getRealPath();

        if (is_string($realPath) && $realPath !== '') {
            $paths[] = $realPath;
        }

        $pathname = $file->getPathname();

        if (
            is_string($pathname)
            && $pathname !== ''
            && ! in_array($pathname, $paths, true)
        ) {
            $paths[] = $pathname;
        }

        return $paths;
    }

    /**
     * @return resource
     */
    private function temporaryStream()
    {
        $stream = fopen('php://temp/maxmemory:5242880', 'w+b');

        if (! is_resource($stream)) {
            throw new RuntimeException(
                'A temporary upload stream could not be created.',
            );
        }

        return $stream;
    }
}
