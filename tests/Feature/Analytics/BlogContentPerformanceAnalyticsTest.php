<?php

namespace Tests\Feature\Analytics;

use App\Enums\AnalyticsEventName;
use App\Models\AnalyticsEvent;
use App\Models\BlogPost;
use App\Models\Order;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogContentPerformanceAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_viewer_receives_blog_content_performance_and_can_export(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->permissions()->attach(Permission::query()->create(['name' => 'view_admin', 'label' => 'View admin']));
        $admin->permissions()->attach(Permission::query()->create(['name' => 'view_reports', 'label' => 'View reports']));
        $reader = User::factory()->create();
        $post = BlogPost::query()->create(['user_id' => $admin->id, 'title' => 'Analytics Story', 'slug' => 'analytics-story', 'content' => '<p>Story</p>', 'status' => BlogPost::STATUS_PUBLISHED, 'is_active' => true, 'published_at' => now()]);
        AnalyticsEvent::query()->create(['event_uuid' => (string) str()->uuid(), 'event_name' => AnalyticsEventName::BlogPostViewed, 'subject_type' => $post->getMorphClass(), 'subject_id' => $post->id, 'user_id' => $reader->id, 'session_id' => 'reader-session', 'occurred_at' => now()]);
        Order::query()->create(['user_id' => $reader->id, 'status' => Order::STATUS_PAID, 'subtotal_cents' => 1800, 'total_cents' => 1800, 'currency' => 'USD', 'paid_at' => now()]);

        $this->actingAs($admin)->get('/admin/analytics/blog?period=7_days')->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Analytics/Blog/Index')->where('report.summary.views', 1)->where('report.summary.influenced_revenue_cents', 1800)->where('report.posts.0.title', 'Analytics Story'));
        $this->actingAs($admin)->get('/admin/analytics/blog/'.$post->slug.'?period=7_days')->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Analytics/Blog/Show')->where('report.performance.influenced_revenue_cents', 1800)->has('report.timeline', 7));
        $this->actingAs($admin)->get('/admin/analytics/blog/export?period=7_days')->assertOk()->assertDownload();
    }
}
