<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssetStatus;
use App\Http\Controllers\Controller;
use App\Models\AdCreative;
use App\Models\Advertiser;
use App\Models\AdvertisingCampaign;
use App\Models\AdvertisingInvoice;
use App\Models\Asset;
use App\Models\AssetFile;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\Download;
use App\Models\LicenseType;
use App\Models\MarketingCampaign;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SponsorshipLead;
use App\Models\SponsorshipProposal;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class AreaDashboardController extends Controller
{
    public function assets(): Response
    {
        return $this->render('assets', 'Assets Dashboard', 'Manage the marketplace catalog, collections, licensing, fulfillment, and asset performance.', [
            ['label' => 'Total assets', 'value' => Asset::query()->count()],
            ['label' => 'Published assets', 'value' => Asset::query()->where('status', AssetStatus::Published)->count()],
            ['label' => 'Files', 'value' => AssetFile::query()->count()],
            ['label' => 'Collections', 'value' => Collection::query()->count()],
        ], [
            ['title' => 'Assets', 'description' => 'Create, review, publish, and maintain marketplace assets.', 'href' => '/admin/assets'],
            ['title' => 'Collections', 'description' => 'Organize related assets into public collections.', 'href' => '/admin/collections'],
            ['title' => 'Configuration Library', 'description' => 'Manage reusable asset configuration templates.', 'href' => '/admin/configuration-templates'],
            ['title' => 'License Types', 'description' => 'Manage license definitions and usage rights.', 'href' => '/admin/license-types'],
            ['title' => 'Orders', 'description' => 'Review marketplace purchases and fulfillment.', 'href' => '/admin/orders'],
            ['title' => 'Downloads', 'description' => 'Review customer download history.', 'href' => '/admin/downloads'],
            ['title' => 'Asset Analytics', 'description' => 'Review views, revenue, conversion, and top-performing assets.', 'href' => '/admin/analytics/assets'],
            ['title' => 'Download Analytics', 'description' => 'Review download and license utilization.', 'href' => '/admin/analytics/downloads'],
        ], Asset::query()->latest()->limit(6)->get(['id','title','status','created_at'])->map(fn (Asset $asset) => [
            'title' => $asset->title,
            'meta' => ucfirst($asset->status->value).' · '.$asset->created_at?->format('M j, Y'),
            'href' => '/admin/assets/'.$asset->id.'/edit',
        ])->all());
    }

    public function blog(): Response
    {
        return $this->render('blog', 'Blog Dashboard', 'Create editorial content, moderate discussion, and understand how stories perform.', [
            ['label' => 'Total posts', 'value' => BlogPost::query()->count()],
            ['label' => 'Published posts', 'value' => BlogPost::query()->where('status', BlogPost::STATUS_PUBLISHED)->count()],
            ['label' => 'Pending comments', 'value' => Comment::query()->where('status', Comment::STATUS_PENDING)->count()],
            ['label' => 'Open reports', 'value' => CommentReport::query()->count()],
        ], [
            ['title' => 'Blog Posts', 'description' => 'Create, edit, schedule, and publish stories.', 'href' => '/admin/blog-posts'],
            ['title' => 'Comments', 'description' => 'Moderate reader comments and replies.', 'href' => '/admin/comments'],
            ['title' => 'Categories', 'description' => 'Manage categories used by blog posts and assets.', 'href' => '/admin/categories'],
            ['title' => 'Tags', 'description' => 'Manage discovery tags across the site.', 'href' => '/admin/tags'],
            ['title' => 'Blog Analytics', 'description' => 'Review readership, engagement, and top-performing stories.', 'href' => '/admin/analytics/blog'],
            ['title' => 'Public Blog', 'description' => 'Open the public stories index.', 'href' => '/blog'],
        ], BlogPost::query()->latest()->limit(6)->get(['id','title','slug','status','created_at'])->map(fn (BlogPost $post) => [
            'title' => $post->title,
            'meta' => ucfirst($post->status).' · '.$post->created_at?->format('M j, Y'),
            'href' => '/admin/blog-posts/'.$post->slug.'/edit',
        ])->all());
    }

    public function advertising(): Response
    {
        return $this->render('advertising', 'Advertising Dashboard', 'Manage advertisers, sponsorship sales, campaign delivery, creatives, inventory, billing, and performance.', [
            ['label' => 'Advertisers', 'value' => Advertiser::query()->count()],
            ['label' => 'Active campaigns', 'value' => AdvertisingCampaign::query()->where('status', 'active')->count()],
            ['label' => 'Creatives awaiting review', 'value' => AdCreative::query()->where('status', 'submitted')->count()],
            ['label' => 'Outstanding invoices', 'value' => AdvertisingInvoice::query()->whereIn('status', ['issued','partially_paid','overdue'])->count()],
        ], [
            ['title' => 'Advertisers', 'description' => 'Manage advertiser organizations and portal memberships.', 'href' => '/admin/advertisers'],
            ['title' => 'Ad Campaigns', 'description' => 'Manage schedules, placements, goals, and creatives.', 'href' => '/admin/ad-campaigns'],
            ['title' => 'Ad Placements', 'description' => 'Configure public advertising locations and dimensions.', 'href' => '/admin/ad-placements'],
            ['title' => 'Sponsorship Pipeline', 'description' => 'Manage leads, activity, follow-ups, and opportunities.', 'href' => '/admin/sponsorship-leads'],
            ['title' => 'Sponsorship Proposals', 'description' => 'Create, send, accept, and convert proposals.', 'href' => '/admin/sponsorship-proposals'],
            ['title' => 'Sponsorship Packages', 'description' => 'Maintain reusable sponsorship products.', 'href' => '/admin/sponsorship-packages'],
            ['title' => 'Ad Inventory', 'description' => 'Review reservations, availability, and capacity.', 'href' => '/admin/ad-inventory'],
            ['title' => 'Advertising Billing', 'description' => 'Manage invoices, payments, refunds, and balances.', 'href' => '/admin/advertising-invoices'],
            ['title' => 'Advertising Analytics', 'description' => 'Review campaign delivery and performance reporting.', 'href' => '/admin/analytics/campaigns'],
        ], SponsorshipProposal::query()->with('advertiser:id,name')->latest()->limit(6)->get()->map(fn (SponsorshipProposal $proposal) => [
            'title' => $proposal->title,
            'meta' => ($proposal->advertiser?->name ?? 'No advertiser').' · '.ucfirst($proposal->status),
            'href' => '/admin/sponsorship-proposals/'.$proposal->id,
        ])->all());
    }

    public function marketing(): Response
    {
        return $this->render('marketing', 'Marketing Dashboard', 'Manage internal promotions, homepage presentation, brand assets, and marketing performance.', [
            ['label' => 'Campaigns', 'value' => MarketingCampaign::query()->count()],
            ['label' => 'Current campaigns', 'value' => MarketingCampaign::query()->current()->count()],
            ['label' => 'Scheduled campaigns', 'value' => MarketingCampaign::query()->where('is_active', true)->where('starts_at', '>', now())->count()],
            ['label' => 'Inactive campaigns', 'value' => MarketingCampaign::query()->where('is_active', false)->count()],
        ], [
            ['title' => 'Marketing Campaigns', 'description' => 'Manage homepage heroes and internal promotions.', 'href' => '/admin/marketing-campaigns'],
            ['title' => 'Marketing Analytics', 'description' => 'Review campaign impressions, clicks, and conversion influence.', 'href' => '/admin/analytics/campaigns'],
            ['title' => 'Site Settings', 'description' => 'Manage public-site presentation and behavior.', 'href' => '/admin/site-settings'],
            ['title' => 'Branding', 'description' => 'Manage logos, colors, and branded media in Site Settings.', 'href' => '/admin/site-settings#branding'],
            ['title' => 'Marketplace Intelligence', 'description' => 'Open the complete executive analytics dashboard.', 'href' => '/admin/analytics'],
            ['title' => 'Public Homepage', 'description' => 'Preview current homepage campaigns and presentation.', 'href' => '/'],
        ], MarketingCampaign::query()->latest()->limit(6)->get(['id','name','is_active','starts_at','ends_at'])->map(fn (MarketingCampaign $campaign) => [
            'title' => $campaign->name,
            'meta' => ($campaign->is_current ? 'Current' : ($campaign->is_active ? 'Scheduled' : 'Inactive')).' · '.($campaign->starts_at?->format('M j, Y') ?? 'No start date'),
            'href' => '/admin/marketing-campaigns/'.$campaign->id.'/edit',
        ])->all());
    }

    public function administration(): Response
    {
        return $this->render('administration', 'Administration Dashboard', 'Manage users, access control, shared taxonomy, licensing configuration, and site settings.', [
            ['label' => 'Users', 'value' => User::query()->count()],
            ['label' => 'Roles', 'value' => Role::query()->count()],
            ['label' => 'Permissions', 'value' => Permission::query()->count()],
            ['label' => 'Categories & tags', 'value' => Category::query()->count() + Tag::query()->count()],
        ], [
            ['title' => 'Users', 'description' => 'Manage accounts, status, roles, and access.', 'href' => '/admin/users'],
            ['title' => 'Roles', 'description' => 'Group permissions into reusable access profiles.', 'href' => '/admin/roles'],
            ['title' => 'Permissions', 'description' => 'Manage individual application capabilities.', 'href' => '/admin/permissions'],
            ['title' => 'Categories', 'description' => 'Manage shared content classifications.', 'href' => '/admin/categories'],
            ['title' => 'Tags', 'description' => 'Manage shared discovery terms.', 'href' => '/admin/tags'],
            ['title' => 'License Types', 'description' => 'Configure marketplace license definitions.', 'href' => '/admin/license-types'],
            ['title' => 'Site Settings', 'description' => 'Manage site identity, branding, and system-wide settings.', 'href' => '/admin/site-settings'],
            ['title' => 'Admin Overview', 'description' => 'Return to the main operational dashboard.', 'href' => '/admin'],
        ], User::query()->latest()->limit(6)->get(['id','name','email','created_at'])->map(fn (User $user) => [
            'title' => $user->name,
            'meta' => $user->email.' · Joined '.$user->created_at?->format('M j, Y'),
            'href' => '/admin/users/'.$user->id,
        ])->all());
    }

    private function render(string $area, string $title, string $description, array $metrics, array $links, array $activity): Response
    {
        return Inertia::render('Admin/Dashboards/Area', [
            'area' => $area,
            'title' => $title,
            'description' => $description,
            'metrics' => $metrics,
            'links' => $links,
            'activity' => $activity,
        ]);
    }
}
