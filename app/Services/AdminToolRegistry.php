<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class AdminToolRegistry
{
    public function forUser(?User $user): array
    {
        if (! $user || ! $user->hasPermission('view_admin')) {
            return [];
        }

        return collect($this->groups())
            ->map(function (array $group) use ($user): array {
                $group['tools'] = collect($group['tools'])
                    ->filter(fn (array $tool): bool => ! isset($tool['permission']) || $user->hasPermission($tool['permission']))
                    ->values()
                    ->all();

                return $group;
            })
            ->filter(fn (array $group): bool => count($group['tools']) > 0)
            ->values()
            ->all();
    }

    private function groups(): array
    {
        return [
            [
                'id' => 'marketplace', 'title' => 'Marketplace', 'description' => 'Assets, collections, commerce, and discovery.', 'icon' => 'Boxes',
                'tools' => [
                    $this->tool('assets', 'Assets', 'Manage marketplace assets and files.', '/admin/assets', 'Boxes', 'manage_images', ['image','video','media','upload','file']),
                    $this->tool('create-asset', 'Upload Asset', 'Create a new marketplace asset.', '/admin/assets/create', 'Upload', 'manage_images', ['new asset','add image','upload video']),
                    $this->tool('collections', 'Collections', 'Create and organize asset collections.', '/admin/collections', 'FolderGit2', 'manage_collections', ['collection','curated','group']),
                    $this->tool('featured-collections', 'Featured & Seasonal Collections', 'Schedule collection promotions on the homepage.', '/admin/discovery/collections', 'CalendarRange', 'manage_collections', ['collection','featured','seasonal','homepage','placement']),
                    $this->tool('trending', 'Trending Assets', 'Review rankings, boosts, and suppression.', '/admin/discovery/trending', 'TrendingUp', 'view_reports', ['popular','ranking','trend','boost']),
                    $this->tool('categories', 'Categories', 'Manage marketplace and content categories.', '/admin/categories', 'Shapes', 'manage_categories', ['taxonomy','classification']),
                    $this->tool('tags', 'Tags', 'Manage reusable taxonomy tags.', '/admin/tags', 'Tags', 'manage_tags', ['keywords','taxonomy']),
                    $this->tool('orders', 'Orders', 'Review purchases and payment status.', '/admin/orders', 'ShoppingCart', 'manage_orders', ['sales','checkout','purchase']),
                    $this->tool('licenses', 'Licenses', 'Review issued customer licenses.', '/admin/licenses', 'KeyRound', 'manage_orders', ['usage rights','rights']),
                    $this->tool('downloads', 'Downloads', 'Review customer download activity.', '/admin/downloads', 'Download', 'manage_orders', ['delivery','files']),
                    $this->tool('license-types', 'License Types', 'Configure license offerings and pricing.', '/admin/license-types', 'BadgeDollarSign', 'manage_license_types', ['price','pricing','rights']),
                    $this->tool('ai-exclusions', 'AI Keyword Exclusions', 'Control words excluded from generated metadata.', '/admin/ai-keyword-exclusions', 'Ban', 'manage_images', ['AI','metadata','keywords']),
                ],
            ],
            [
                'id' => 'content', 'title' => 'Content', 'description' => 'Stories, public pages, comments, and page guidance.', 'icon' => 'FileText',
                'tools' => [
                    $this->tool('blog-posts', 'Blog Posts', 'Create and manage stories and articles.', '/admin/blog-posts', 'Newspaper', 'manage_blog_posts', ['article','story','content']),
                    $this->tool('create-blog-post', 'Create Blog Post', 'Start a new story or article.', '/admin/blog-posts/create', 'FilePlus2', 'manage_blog_posts', ['new article','write story']),
                    $this->tool('public-pages', 'Public Pages', 'Manage informational and legal pages.', '/admin/public-pages', 'PanelsTopLeft', 'manage_public_pages', ['page','legal','about']),
                    $this->tool('comments', 'Comments', 'Moderate reader discussions and reports.', '/admin/comments', 'MessageSquare', 'manage_comments', ['moderation','discussion']),
                    $this->tool('page-help', 'Page Help', 'Manage contextual instructions for application pages.', '/admin/page-help', 'CircleHelp', 'view_page_help_admin', ['documentation','instructions','help']),
                ],
            ],
            [
                'id' => 'marketing', 'title' => 'Marketing & Advertising', 'description' => 'Campaigns, advertisers, sponsorships, and promotion.', 'icon' => 'Megaphone',
                'tools' => [
                    $this->tool('marketing-campaigns', 'Marketing Campaigns', 'Create and track promotional campaigns.', '/admin/marketing-campaigns', 'Megaphone', 'manage_site_settings', ['promotion','social','campaign']),
                    $this->tool('advertisers', 'Advertisers', 'Manage advertiser accounts and contacts.', '/admin/advertisers', 'Building2', 'manage_advertisers', ['sponsor','company']),
                    $this->tool('ad-campaigns', 'Advertising Campaigns', 'Manage paid advertising campaigns.', '/admin/ad-campaigns', 'BadgeDollarSign', 'manage_ad_campaigns', ['advertising','ads']),
                    $this->tool('sponsorship-pipeline', 'Sponsorship Pipeline', 'Review sponsorship leads and sales activity.', '/admin/sponsorship-leads', 'Handshake', 'view_sponsorship_sales', ['lead','sponsor','sales']),
                    $this->tool('proposals', 'Sponsorship Proposals', 'Create and manage sponsorship proposals.', '/admin/sponsorship-proposals', 'FileSignature', 'manage_sponsorship_proposals', ['proposal','sponsor']),
                ],
            ],
            [
                'id' => 'customers', 'title' => 'Customers & Support', 'description' => 'Accounts, access, and customer assistance.', 'icon' => 'Users',
                'tools' => [
                    $this->tool('users', 'Users', 'Manage customer and staff accounts.', '/admin/users', 'Users', 'manage_users', ['account','member','customer']),
                    $this->tool('roles', 'Roles', 'Configure role-based access.', '/admin/roles', 'ShieldCheck', 'manage_roles', ['security','access']),
                    $this->tool('permissions', 'Permissions', 'Manage individual system permissions.', '/admin/permissions', 'KeyRound', 'manage_permissions', ['security','access']),
                    $this->tool('support-dashboard', 'Support Dashboard', 'Review support workload and status.', '/admin/support/dashboard', 'LifeBuoy', 'view_support_tickets', ['ticket','help desk']),
                    $this->tool('support-tickets', 'All Support Tickets', 'Search and manage customer requests.', '/admin/support/tickets', 'Inbox', 'view_support_tickets', ['ticket','issue','request']),
                    $this->tool('support-reports', 'Support Reports', 'Analyze support volume and response performance.', '/admin/support/reports', 'BarChart3', 'view_support_reports', ['ticket analytics']),
                    $this->tool('support-categories', 'Support Categories', 'Configure ticket categories.', '/admin/support/categories', 'Tags', 'manage_support_categories', ['ticket taxonomy']),
                ],
            ],
            [
                'id' => 'analytics', 'title' => 'Analytics & Reporting', 'description' => 'Marketplace performance, revenue, search, and operations.', 'icon' => 'BarChart3',
                'tools' => [
                    $this->tool('analytics-overview', 'Marketplace Intelligence', 'Open the executive analytics dashboard.', '/admin/analytics', 'BarChart3', 'view_reports', ['reports','dashboard','KPI']),
                    $this->tool('financial-report', 'Financial Report', 'Review revenue, orders, and financial performance.', '/admin/analytics/financial', 'CircleDollarSign', 'view_reports', ['revenue','money','sales']),
                    $this->tool('asset-performance', 'Asset Performance', 'Compare views, purchases, and downloads by asset.', '/admin/analytics/assets', 'Activity', 'view_reports', ['image analytics','media performance']),
                    $this->tool('customer-conversion', 'Customer Conversion', 'Analyze customer journeys and conversion.', '/admin/analytics/customers', 'UserRoundCheck', 'view_reports', ['funnel','customers']),
                    $this->tool('search-discovery', 'Search Discovery', 'Review searches, filters, and no-result terms.', '/admin/analytics/search', 'Search', 'view_reports', ['query','autocomplete','search analytics']),
                    $this->tool('marketing-performance', 'Marketing Performance', 'Measure campaign traffic and conversion.', '/admin/analytics/campaigns', 'Megaphone', 'view_reports', ['campaign analytics']),
                    $this->tool('download-utilization', 'Download & License Utilization', 'Review downloads and license use.', '/admin/analytics/downloads', 'Download', 'view_reports', ['license analytics']),
                    $this->tool('operations', 'Marketplace Operations', 'Review marketplace health and operations.', '/admin/analytics/operations', 'Gauge', 'view_reports', ['operations','health']),
                ],
            ],
            [
                'id' => 'system', 'title' => 'System & Configuration', 'description' => 'Site branding, settings, templates, and administration.', 'icon' => 'Settings',
                'tools' => [
                    $this->tool('site-settings', 'Site Settings', 'Manage branding, contact, SEO, and site behavior.', '/admin/site-settings', 'Settings', 'manage_site_settings', ['branding','logo','SEO','configuration']),
                    $this->tool('configuration-templates', 'Configuration Templates', 'Manage reusable configuration presets.', '/admin/configuration-templates', 'SlidersHorizontal', 'manage_site_settings', ['template','preset']),
                    $this->tool('administration-dashboard', 'Administration Dashboard', 'Open system administration summaries.', '/admin/administration-dashboard', 'ShieldCheck', 'view_admin', ['system','admin']),
                ],
            ],
        ];
    }

    private function tool(string $id, string $title, string $description, string $href, string $icon, string $permission, array $keywords = []): array
    {
        return compact('id', 'title', 'description', 'href', 'icon', 'permission', 'keywords');
    }
}
