<?php

namespace App\Services\PublicPages;

use App\Models\PublicPage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicPageMediaService
{
    public function storeOriginal(UploadedFile $file, PublicPage $page): string
    {
        return $file->storeAs("public-pages/{$page->id}/header/original", 'source-'.Str::uuid().'.'.$this->extension($file), 'public');
    }

    public function storeRendered(UploadedFile $file, PublicPage $page): string
    {
        return $file->storeAs("public-pages/{$page->id}/header/rendered", 'page-header-'.Str::uuid().'.'.$this->extension($file), 'public');
    }

    public function delete(?string $path): void
    {
        if ($path) Storage::disk('public')->delete($path);
    }

    public function deleteDirectory(PublicPage $page): void
    {
        Storage::disk('public')->deleteDirectory("public-pages/{$page->id}");
    }

    private function extension(UploadedFile $file): string
    {
        return strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
    }
}
