<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CollectionCoverMediaService
{
    public function storeOriginal(UploadedFile $file, int $collectionId): string
    {
        return $file->storeAs(
            $this->directory($collectionId).'/original',
            'source-'.Str::uuid().'.'.$this->extension($file),
            'public',
        );
    }

    public function storeRendered(UploadedFile $file, int $collectionId): string
    {
        return $file->storeAs(
            $this->directory($collectionId).'/rendered',
            'collection-cover-'.Str::uuid().'.'.$this->extension($file),
            'public',
        );
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    public function deleteCollectionDirectory(int $collectionId): void
    {
        Storage::disk('public')->deleteDirectory($this->directory($collectionId));
    }

    private function directory(int $collectionId): string
    {
        return "collections/{$collectionId}/cover";
    }

    private function extension(UploadedFile $file): string
    {
        return strtolower(
            $file->guessExtension()
            ?: $file->getClientOriginalExtension()
            ?: 'jpg',
        );
    }
}
