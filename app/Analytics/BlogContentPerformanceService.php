<?php

namespace App\Analytics;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;

class BlogContentPerformanceService
{
    public function report(AnalyticsPeriod $period, array $filters = []): array
    {
        $posts = $this->postRows($period, $filters);
        $views = (int) $posts->sum('views');
        $uniqueReaders = (int) $posts->sum('unique_readers');
        $comments = (int) $posts->sum('comments');
        $influencedRevenue = (int) $posts->sum('influenced_revenue_cents');

        return [
            'summary' => [
                'published_posts' => $posts->count(),
                'views' => $views,
                'unique_readers' => $uniqueReaders,
                'approved_comments' => $comments,
                'engagement_rate_percent' => $views > 0 ? round((($comments + (int) $posts->sum('marketplace_actions')) / $views) * 100, 1) : 0,
                'influenced_buyers' => (int) $posts->sum('influenced_buyers'),
                'influenced_revenue_cents' => $influencedRevenue,
            ],
            'posts' => $posts->values()->all(),
            'authors' => $this->groupRows($posts, 'author_name'),
            'categories' => $this->categoryRows($posts),
            'opportunities' => [
                'high_traffic_low_engagement' => $posts->filter(fn (array $row) => $row['views'] >= 5 && $row['engagement_rate_percent'] < 5)->take(10)->values()->all(),
                'conversion_drivers' => $posts->filter(fn (array $row) => $row['influenced_revenue_cents'] > 0)->sortByDesc('influenced_revenue_cents')->take(10)->values()->all(),
                'stale_content' => $posts->filter(fn (array $row) => $row['views'] === 0)->take(10)->values()->all(),
            ],
        ];
    }

    public function detail(BlogPost $post, AnalyticsPeriod $period): array
    {
        $row = $this->postRows($period, ['post_id' => $post->id])->first();
        $timeline = [];

        for ($date = $period->start->startOfDay(); $date->lte($period->end); $date = $date->addDay()) {
            $start = $date->startOfDay();
            $end = $date->endOfDay();
            $events = $this->viewEventsQuery($post, $start, $end)->get();
            $readerIds = $events->pluck('user_id')->filter()->unique()->values();

            $timeline[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('M j'),
                'views' => $events->count(),
                'unique_readers' => $this->uniqueReaderCount($events),
                'comments' => $post->approvedComments()->whereBetween('created_at', [$start, $end])->count(),
                'orders' => $readerIds->isEmpty() ? 0 : Order::query()->whereIn('user_id', $readerIds)->where('status', Order::STATUS_PAID)->whereBetween('paid_at', [$start, $end])->count(),
            ];
        }

        return [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'author' => $post->author?->name,
                'published_at' => $post->published_at?->toIso8601String(),
                'status' => $post->status,
            ],
            'performance' => $row,
            'timeline' => $timeline,
            'categories' => $post->categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])->all(),
            'tags' => $post->tags->map(fn ($tag) => ['id' => $tag->id, 'name' => $tag->name])->all(),
        ];
    }

    public function exportRows(AnalyticsPeriod $period, array $filters = []): array
    {
        return $this->postRows($period, $filters)->map(fn (array $row) => [
            $row['post_id'], $row['title'], $row['author_name'], implode('|', $row['category_names']), $row['views'], $row['unique_readers'],
            $row['comments'], $row['marketplace_actions'], $row['influenced_buyers'], $row['influenced_orders'], $row['influenced_revenue_cents'], $row['engagement_rate_percent'],
        ])->all();
    }

    private function postRows(AnalyticsPeriod $period, array $filters = []): Collection
    {
        $query = BlogPost::query()->with(['author:id,name', 'categories:id,name', 'tags:id,name'])->where('status', BlogPost::STATUS_PUBLISHED);
        if (! empty($filters['post_id'])) $query->whereKey($filters['post_id']);
        if (! empty($filters['search'])) $query->where(fn ($q) => $q->where('title', 'like', '%'.$filters['search'].'%')->orWhere('excerpt', 'like', '%'.$filters['search'].'%'));
        if (! empty($filters['author_id'])) $query->where('user_id', $filters['author_id']);
        if (! empty($filters['category_id'])) $query->whereHas('categories', fn ($q) => $q->whereKey($filters['category_id']));

        return $query->get()->map(function (BlogPost $post) use ($period): array {
            $events = $this->viewEventsQuery($post, $period->start, $period->end)->get();
            $readerIds = $events->pluck('user_id')->filter()->unique()->values();
            $marketplaceActions = $readerIds->isEmpty() ? 0 : AnalyticsEvent::query()
                ->whereIn('user_id', $readerIds)
                ->whereIn('event_name', [AnalyticsEventName::AssetFavorited->value, AnalyticsEventName::AssetAddedToCart->value, AnalyticsEventName::CheckoutStarted->value])
                ->whereBetween('occurred_at', [$period->start, $period->end])->count();
            $orders = $readerIds->isEmpty() ? collect() : Order::query()->whereIn('user_id', $readerIds)->where('status', Order::STATUS_PAID)->whereBetween('paid_at', [$period->start, $period->end])->get();
            $comments = $post->approvedComments()->whereBetween('created_at', [$period->start, $period->end])->count();
            $views = $events->count();

            return [
                'post_id' => $post->id,
                'slug' => $post->slug,
                'title' => $post->title,
                'author_id' => $post->user_id,
                'author_name' => $post->author?->name ?? 'Unknown author',
                'category_names' => $post->categories->pluck('name')->all(),
                'published_at' => $post->published_at?->toIso8601String(),
                'views' => $views,
                'unique_readers' => $this->uniqueReaderCount($events),
                'registered_readers' => $readerIds->count(),
                'comments' => $comments,
                'marketplace_actions' => $marketplaceActions,
                'influenced_buyers' => $orders->pluck('user_id')->unique()->count(),
                'influenced_orders' => $orders->count(),
                'influenced_revenue_cents' => (int) $orders->sum('total_cents'),
                'engagement_rate_percent' => $views > 0 ? round((($comments + $marketplaceActions) / $views) * 100, 1) : 0,
            ];
        })->sortByDesc(fn (array $row) => ($row['influenced_revenue_cents'] * 1000000) + ($row['views'] * 1000) + $row['comments'])->values();
    }

    private function viewEventsQuery(BlogPost $post, $start, $end)
    {
        return AnalyticsEvent::query()
            ->where('event_name', AnalyticsEventName::BlogPostViewed->value)
            ->where('subject_type', $post->getMorphClass())
            ->where('subject_id', $post->id)
            ->whereBetween('occurred_at', [$start, $end]);
    }

    private function uniqueReaderCount(Collection $events): int
    {
        return $events->map(fn (AnalyticsEvent $event) => $event->user_id ? 'user:'.$event->user_id : ($event->session_id ? 'session:'.$event->session_id : 'event:'.$event->id))->unique()->count();
    }

    private function groupRows(Collection $posts, string $key): array
    {
        return $posts->groupBy($key)->map(fn (Collection $rows, string $label) => [
            'label' => $label,
            'posts' => $rows->count(),
            'views' => (int) $rows->sum('views'),
            'comments' => (int) $rows->sum('comments'),
            'revenue_cents' => (int) $rows->sum('influenced_revenue_cents'),
            'units' => (int) $rows->sum('influenced_orders'),
        ])->sortByDesc('revenue_cents')->values()->all();
    }

    private function categoryRows(Collection $posts): array
    {
        return $posts->flatMap(fn (array $row) => collect($row['category_names'])->map(fn (string $name) => ['name' => $name, 'row' => $row]))
            ->groupBy('name')->map(fn (Collection $items, string $label) => [
                'label' => $label,
                'posts' => $items->count(),
                'views' => (int) $items->sum(fn ($item) => $item['row']['views']),
                'comments' => (int) $items->sum(fn ($item) => $item['row']['comments']),
                'revenue_cents' => (int) $items->sum(fn ($item) => $item['row']['influenced_revenue_cents']),
                'units' => (int) $items->sum(fn ($item) => $item['row']['influenced_orders']),
            ])->sortByDesc('revenue_cents')->values()->all();
    }
}
