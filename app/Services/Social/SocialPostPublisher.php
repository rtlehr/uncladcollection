<?php

namespace App\Services\Social;

use App\Models\SocialPost;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SocialPostPublisher
{
    public function __construct(private readonly XApiClient $x) {}

    public function publish(SocialPost $post): SocialPost
    {
        if ($post->status === 'published') {
            return $post;
        }

        $post->forceFill([
            'status' => 'publishing',
            'attempts' => $post->attempts + 1,
            'error_message' => null,
        ])->save();

        try {
            $absolutePath = null;
            if ($post->media_path) {
                $disk = Storage::disk($post->media_disk ?: 'public');
                $absolutePath = $disk->path($post->media_path);
            }

            $result = $this->x->publish($post->text, $absolutePath, $post->media_type);

            $post->forceFill([
                'status' => 'published',
                'published_at' => now(),
                'x_post_id' => $result['id'],
                'x_post_url' => $result['url'],
                'api_response' => $result['response'],
                'error_message' => null,
            ])->save();
        } catch (Throwable $e) {
            report($e);
            $post->forceFill([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ])->save();
            throw $e;
        }

        return $post->fresh();
    }
}
