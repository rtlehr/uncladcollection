<?php

namespace App\Console\Commands;

use App\Models\SocialPost;
use App\Services\Social\SocialPostPublisher;
use Illuminate\Console\Command;
use Throwable;

class PublishScheduledSocialPosts extends Command
{
    protected $signature = 'social:x-publish {--limit=20 : Maximum posts to publish in one run}';
    protected $description = 'Publish due scheduled X posts.';

    public function handle(SocialPostPublisher $publisher): int
    {
        $posts = SocialPost::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit(max(1, min(100, (int) $this->option('limit'))))
            ->get();

        foreach ($posts as $post) {
            try {
                $publisher->publish($post);
                $this->info("Published social post {$post->id}.");
            } catch (Throwable $e) {
                $this->error("Social post {$post->id} failed: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
