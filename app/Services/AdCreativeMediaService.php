<?php

namespace App\Services;

use App\Models\AdCreative;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdCreativeMediaService
{
    public function storeImage(UploadedFile $original, UploadedFile $edited, string $directory): array
    {
        return [
            'original_media_path' => $original->storeAs($directory.'/original', 'source-'.Str::uuid().'.'.$this->extension($original), 'public'),
            'media_path' => $edited->storeAs($directory.'/rendered', 'creative-'.Str::uuid().'.'.$this->extension($edited), 'public'),
        ];
    }

    public function storeVideo(UploadedFile $video, string $directory): array
    {
        return [
            'original_media_path' => null,
            'media_path' => $video->store($directory.'/video', 'public'),
        ];
    }

    public function deleteCreativeMedia(AdCreative $creative): void
    {
        Storage::disk('public')->delete(array_filter([$creative->media_path, $creative->original_media_path]));
    }

    private function extension(UploadedFile $file): string
    {
        return strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
    }
}
