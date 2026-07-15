<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MarketingCampaignMediaService
{
    public function storeOriginal(UploadedFile $file, string $directory): string
    {
        return $file->storeAs(
            $directory.'/original',
            'source-'.Str::uuid().'.'.$this->extension($file),
            'public',
        );
    }

    public function storeEdited(UploadedFile $file, string $directory): string
    {
        return $file->storeAs(
            $directory.'/rendered',
            'marketing-hero-'.Str::uuid().'.'.$this->extension($file),
            'public',
        );
    }

    public function storeVideo(UploadedFile $file, string $directory): string
    {
        return $file->store($directory.'/video', 'public');
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
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
