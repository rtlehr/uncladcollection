<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AdminBlogPostController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AreaDashboardController;
use App\Http\Controllers\Admin\AnalyticsDashboardController;
use App\Http\Controllers\Admin\AssetPerformanceReportController;
use App\Http\Controllers\Admin\BlogContentPerformanceReportController;
use App\Http\Controllers\Admin\FinancialReportController;
use App\Http\Controllers\Admin\AdminDownloadController;
use App\Http\Controllers\Admin\AdminLicenseController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\AssetAiSuggestionController;
use App\Http\Controllers\Admin\AssetConfigurationTemplateController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\CustomerConversionReportController;
use App\Http\Controllers\Admin\CommentModerationController;
use App\Http\Controllers\Admin\ImageController;
use App\Http\Controllers\Admin\LicenseTypeController;
use App\Http\Controllers\Admin\MarketingCampaignController;
use App\Http\Controllers\Admin\MarketingCampaignPerformanceReportController;
use App\Http\Controllers\Admin\AdvertiserController;
use App\Http\Controllers\Admin\AdvertiserMembershipController;
use App\Http\Controllers\Admin\AdPlacementController;
use App\Http\Controllers\Admin\AdvertisingCampaignController;
use App\Http\Controllers\Admin\AdvertisingInvoiceController;
use App\Http\Controllers\Admin\AdCreativeController;
use App\Http\Controllers\Admin\MarketplaceOperationsReportController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SearchDiscoveryReportController;
use App\Http\Controllers\Admin\DownloadLicenseUtilizationReportController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SponsorshipPackageController;
use App\Http\Controllers\Admin\SponsorshipLeadController;
use App\Http\Controllers\Admin\SponsorshipProposalController;
use App\Http\Controllers\Admin\AdInventoryController;


Route::middleware(['auth', 'verified', 'permission:view_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)
            ->name('dashboard');

        Route::get('/assets-dashboard', [AreaDashboardController::class, 'assets'])->name('dashboards.assets');
        Route::get('/blog-dashboard', [AreaDashboardController::class, 'blog'])->name('dashboards.blog');
        Route::get('/advertising-dashboard', [AreaDashboardController::class, 'advertising'])->name('dashboards.advertising');
        Route::get('/marketing-dashboard', [AreaDashboardController::class, 'marketing'])->name('dashboards.marketing');
        Route::get('/administration-dashboard', [AreaDashboardController::class, 'administration'])->name('dashboards.administration');

        Route::get('/analytics', AnalyticsDashboardController::class)
            ->middleware('permission:view_reports')
            ->name('analytics.index');

        Route::get('/analytics/financial', [FinancialReportController::class, 'index'])
            ->middleware('permission:view_reports')
            ->name('analytics.financial');

        Route::get('/analytics/financial/export', [FinancialReportController::class, 'export'])
            ->middleware('permission:view_reports')
            ->name('analytics.financial.export');

        Route::get('/analytics/assets', [AssetPerformanceReportController::class, 'index'])
            ->middleware('permission:view_reports')
            ->name('analytics.assets.index');

        Route::get('/analytics/assets/export', [AssetPerformanceReportController::class, 'export'])
            ->middleware('permission:view_reports')
            ->name('analytics.assets.export');

        Route::get('/analytics/assets/{asset}', [AssetPerformanceReportController::class, 'show'])
            ->middleware('permission:view_reports')
            ->name('analytics.assets.show');


        Route::get('/analytics/customers', [CustomerConversionReportController::class, 'index'])
            ->middleware('permission:view_reports')
            ->name('analytics.customers.index');

        Route::get('/analytics/customers/export', [CustomerConversionReportController::class, 'export'])
            ->middleware('permission:view_reports')
            ->name('analytics.customers.export');

        Route::get('/analytics/customers/{user}', [CustomerConversionReportController::class, 'show'])
            ->middleware('permission:view_reports')
            ->name('analytics.customers.show');


        Route::get('/analytics/blog', [BlogContentPerformanceReportController::class, 'index'])
            ->middleware('permission:view_reports')
            ->name('analytics.blog.index');

        Route::get('/analytics/blog/export', [BlogContentPerformanceReportController::class, 'export'])
            ->middleware('permission:view_reports')
            ->name('analytics.blog.export');

        Route::get('/analytics/blog/{blogPost}', [BlogContentPerformanceReportController::class, 'show'])
            ->middleware('permission:view_reports')
            ->name('analytics.blog.show');

        Route::get('/analytics/campaigns', [MarketingCampaignPerformanceReportController::class, 'index'])
            ->middleware('permission:view_reports')->name('analytics.campaigns.index');
        Route::get('/analytics/campaigns/export', [MarketingCampaignPerformanceReportController::class, 'export'])
            ->middleware('permission:view_reports')->name('analytics.campaigns.export');
        Route::get('/analytics/campaigns/{marketingCampaign}', [MarketingCampaignPerformanceReportController::class, 'show'])
            ->middleware('permission:view_reports')->name('analytics.campaigns.show');

        Route::get('/analytics/search', [SearchDiscoveryReportController::class, 'index'])
            ->middleware('permission:view_reports')->name('analytics.search.index');
        Route::get('/analytics/search/export', [SearchDiscoveryReportController::class, 'export'])
            ->middleware('permission:view_reports')->name('analytics.search.export');
        Route::get('/analytics/search/{term}', [SearchDiscoveryReportController::class, 'show'])
            ->middleware('permission:view_reports')->where('term', '.*')->name('analytics.search.show');

        Route::get('/analytics/downloads', [DownloadLicenseUtilizationReportController::class, 'index'])
            ->middleware('permission:view_reports')->name('analytics.downloads.index');
        Route::get('/analytics/downloads/export', [DownloadLicenseUtilizationReportController::class, 'export'])
            ->middleware('permission:view_reports')->name('analytics.downloads.export');
        Route::get('/analytics/downloads/{license}', [DownloadLicenseUtilizationReportController::class, 'show'])
            ->middleware('permission:view_reports')->name('analytics.downloads.show');

        Route::get('/analytics/operations', [MarketplaceOperationsReportController::class, 'index'])
            ->middleware('permission:view_reports')->name('analytics.operations.index');
        Route::get('/analytics/operations/export', [MarketplaceOperationsReportController::class, 'export'])
            ->middleware('permission:view_reports')->name('analytics.operations.export');
        Route::get('/analytics/operations/{order}', [MarketplaceOperationsReportController::class, 'show'])
            ->middleware('permission:view_reports')->name('analytics.operations.show');

        Route::get('/site-settings', [SiteSettingController::class, 'index'])
            ->middleware('permission:manage_site_settings')
            ->name('site-settings.index');

        Route::match(['post', 'put'], '/site-settings', [SiteSettingController::class, 'update'])
            ->middleware('permission:manage_site_settings')
            ->name('site-settings.update');



        Route::resource('sponsorship-packages', SponsorshipPackageController::class)->except(['show'])->middleware('permission:manage_sponsorship_packages');
        Route::resource('sponsorship-leads', SponsorshipLeadController::class)->except(['destroy'])->middleware('permission:manage_sponsorship_leads');
        Route::post('/sponsorship-leads/{sponsorshipLead}/activities', [SponsorshipLeadController::class, 'activity'])->middleware('permission:manage_sponsorship_leads')->name('sponsorship-leads.activities.store');
        Route::resource('sponsorship-proposals', SponsorshipProposalController::class)->except(['destroy'])->middleware('permission:manage_sponsorship_proposals');
        Route::post('/sponsorship-proposals/{sponsorshipProposal}/status', [SponsorshipProposalController::class, 'status'])->middleware('permission:manage_sponsorship_proposals')->name('sponsorship-proposals.status');
        Route::post('/sponsorship-proposals/{sponsorshipProposal}/convert', [SponsorshipProposalController::class, 'convert'])->middleware('permission:convert_sponsorship_proposals')->name('sponsorship-proposals.convert');
        Route::get('/ad-inventory', [AdInventoryController::class, 'index'])->middleware('permission:manage_ad_inventory')->name('ad-inventory.index');

        Route::resource('advertisers', AdvertiserController::class)->except(['show'])->middleware('permission:manage_advertisers');
        Route::post('/advertisers/{advertiser}/memberships', [AdvertiserMembershipController::class, 'store'])->middleware('permission:manage_advertisers')->name('advertisers.memberships.store');
        Route::patch('/advertisers/{advertiser}/memberships/{membership}', [AdvertiserMembershipController::class, 'update'])->middleware('permission:manage_advertisers')->name('advertisers.memberships.update');
        Route::delete('/advertisers/{advertiser}/memberships/{membership}', [AdvertiserMembershipController::class, 'destroy'])->middleware('permission:manage_advertisers')->name('advertisers.memberships.destroy');
        Route::resource('ad-placements', AdPlacementController::class)->except(['show'])->middleware('permission:manage_ad_placements');
        Route::resource('ad-campaigns', AdvertisingCampaignController::class)->middleware('permission:manage_ad_campaigns');
        Route::get('/advertising-invoices', [AdvertisingInvoiceController::class, 'index'])->middleware('permission:view_advertising_billing')->name('advertising-invoices.index');
        Route::get('/advertising-invoices/create', [AdvertisingInvoiceController::class, 'create'])->middleware('permission:manage_advertising_invoices')->name('advertising-invoices.create');
        Route::post('/advertising-invoices', [AdvertisingInvoiceController::class, 'store'])->middleware('permission:manage_advertising_invoices')->name('advertising-invoices.store');
        Route::get('/advertising-invoices/{advertisingInvoice}', [AdvertisingInvoiceController::class, 'show'])->middleware('permission:view_advertising_billing')->name('advertising-invoices.show');
        Route::get('/advertising-invoices/{advertisingInvoice}/edit', [AdvertisingInvoiceController::class, 'edit'])->middleware('permission:manage_advertising_invoices')->name('advertising-invoices.edit');
        Route::put('/advertising-invoices/{advertisingInvoice}', [AdvertisingInvoiceController::class, 'update'])->middleware('permission:manage_advertising_invoices')->name('advertising-invoices.update');
        Route::post('/advertising-invoices/{advertisingInvoice}/issue', [AdvertisingInvoiceController::class, 'issue'])->middleware('permission:manage_advertising_invoices')->name('advertising-invoices.issue');
        Route::post('/advertising-invoices/{advertisingInvoice}/void', [AdvertisingInvoiceController::class, 'void'])->middleware('permission:manage_advertising_invoices')->name('advertising-invoices.void');
        Route::post('/advertising-invoices/{advertisingInvoice}/payments', [AdvertisingInvoiceController::class, 'payment'])->middleware('permission:record_advertising_payments')->name('advertising-invoices.payments.store');
        Route::post('/advertising-invoices/{advertisingInvoice}/refunds', [AdvertisingInvoiceController::class, 'refund'])->middleware('permission:refund_advertising_payments')->name('advertising-invoices.refunds.store');
        Route::post('/advertising-invoices/{advertisingInvoice}/checkout', [AdvertisingInvoiceController::class, 'checkout'])->middleware('permission:record_advertising_payments')->name('advertising-invoices.checkout');
        Route::post('/ad-campaigns/{adCampaign}/submit', [AdvertisingCampaignController::class, 'submit'])->middleware('permission:manage_ad_campaigns')->name('ad-campaigns.submit');
        Route::post('/ad-campaigns/{adCampaign}/decision', [AdvertisingCampaignController::class, 'approve'])->middleware('permission:approve_ad_campaigns')->name('ad-campaigns.decision');
        Route::get('/ad-campaigns/{adCampaign}/creatives', [AdCreativeController::class, 'index'])->middleware('permission:manage_ad_campaigns')->name('ad-campaigns.creatives.index');
        Route::get('/ad-campaigns/{adCampaign}/creatives/create', [AdCreativeController::class, 'create'])->middleware('permission:manage_ad_campaigns')->name('ad-campaigns.creatives.create');
        Route::post('/ad-campaigns/{adCampaign}/creatives', [AdCreativeController::class, 'store'])->middleware('permission:manage_ad_campaigns')->name('ad-campaigns.creatives.store');
        Route::get('/ad-campaigns/{adCampaign}/creatives/{creative}/edit', [AdCreativeController::class, 'edit'])->middleware('permission:manage_ad_campaigns')->name('ad-campaigns.creatives.edit');
        Route::put('/ad-campaigns/{adCampaign}/creatives/{creative}', [AdCreativeController::class, 'update'])->middleware('permission:manage_ad_campaigns')->name('ad-campaigns.creatives.update');
        Route::delete('/ad-campaigns/{adCampaign}/creatives/{creative}', [AdCreativeController::class, 'destroy'])->middleware('permission:manage_ad_campaigns')->name('ad-campaigns.creatives.destroy');
        Route::post('/ad-campaigns/{adCampaign}/creatives/{creative}/submit', [AdCreativeController::class, 'submit'])->middleware('permission:manage_ad_campaigns')->name('ad-campaigns.creatives.submit');
        Route::post('/ad-campaigns/{adCampaign}/creatives/{creative}/decision', [AdCreativeController::class, 'decision'])->middleware('permission:approve_ad_campaigns')->name('ad-campaigns.creatives.decision');
        Route::post('/ad-campaigns/{adCampaign}/creatives/{creative}/return-to-draft', [AdCreativeController::class, 'returnToDraft'])->middleware('permission:approve_ad_campaigns')->name('ad-campaigns.creatives.return-to-draft');

        Route::resource('marketing-campaigns', MarketingCampaignController::class)
            ->except(['show'])
            ->middleware('permission:manage_site_settings');

        Route::resource('permissions', PermissionController::class)
            ->except(['show'])
            ->middleware('permission:manage_permissions');

        Route::resource('roles', RoleController::class)
            ->except(['show'])
            ->middleware('permission:manage_roles');

        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:manage_users')
            ->name('users.index');

        Route::get('/users/{user}', [UserController::class, 'show'])
            ->middleware('permission:manage_users')
            ->name('users.show');

        Route::get('/users/{user}/edit', [UserController::class, 'edit'])
            ->middleware('permission:manage_users')
            ->name('users.edit');

        Route::put('/users/{user}', [UserController::class, 'update'])
            ->middleware('permission:manage_users')
            ->name('users.update');

        Route::resource('categories', CategoryController::class)
            ->middleware('permission:manage_categories');

        Route::resource('tags', TagController::class)
            ->middleware('permission:manage_tags');

        Route::resource('collections', CollectionController::class)
            ->middleware('permission:manage_collections');

        Route::resource('images', ImageController::class)
            ->middleware('permission:manage_images');

        Route::post('/assets/{asset}/ai-suggestions', [AssetAiSuggestionController::class, 'store'])
            ->middleware('permission:manage_images')
            ->name('assets.ai-suggestions.store');

        Route::post('/assets/{asset}/ai-suggestions/{suggestion}/apply', [AssetAiSuggestionController::class, 'apply'])
            ->middleware('permission:manage_images')
            ->name('assets.ai-suggestions.apply');

        Route::get('/assets/{asset}/files/{assetFile}/preview', [AssetController::class, 'previewFile'])
            ->middleware('permission:manage_images')
            ->name('assets.files.preview');

        Route::post('/assets/{asset}/files', [AssetController::class, 'addFiles'])
            ->middleware('permission:manage_images')
            ->name('assets.files.store');

        Route::put('/assets/{asset}/files/order', [AssetController::class, 'reorderFiles'])
            ->middleware('permission:manage_images')
            ->name('assets.files.order');

        Route::patch('/assets/{asset}/files/{assetFile}', [AssetController::class, 'updateFile'])
            ->middleware('permission:manage_images')
            ->name('assets.files.update');

        Route::post('/assets/{asset}/files/{assetFile}/replace', [AssetController::class, 'replaceFile'])
            ->middleware('permission:manage_images')
            ->name('assets.files.replace');

        Route::delete('/assets/{asset}/files/{assetFile}', [AssetController::class, 'destroyFile'])
            ->middleware('permission:manage_images')
            ->name('assets.files.destroy');

        Route::put('/assets/{asset}/configurations', [AssetController::class, 'updateConfigurations'])
            ->middleware('permission:manage_images')
            ->name('assets.configurations.update');

        Route::post('/assets/{asset}/presentation', [AssetController::class, 'updatePresentation'])
            ->middleware(['permission:manage_images'])
            ->name('assets.presentation.update');

        Route::put('/assets/{asset}/relationships', [AssetController::class, 'updateRelationships'])
            ->middleware('permission:manage_images')
            ->name('assets.relationships.update');

        Route::put('/assets/{asset}/offerings', [AssetController::class, 'updateOfferings'])
            ->middleware('permission:manage_images')
            ->name('assets.offerings.update');

        Route::resource('assets', AssetController::class)
            ->except(['show'])
            ->middleware('permission:manage_images');

        Route::resource('configuration-templates', AssetConfigurationTemplateController::class)
            ->except(['show'])
            ->middleware('permission:manage_images');

        Route::resource('license-types', LicenseTypeController::class)
            ->except(['show'])
            ->middleware('permission:manage_license_types');

        Route::get('/orders', [AdminOrderController::class, 'index'])
            ->middleware('permission:manage_orders')
            ->name('orders.index');

        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])
            ->middleware('permission:manage_orders')
            ->name('orders.show');


        Route::patch('/orders/{order}/fulfillment', [AdminOrderController::class, 'updateFulfillment'])
            ->middleware('permission:manage_orders')
            ->name('orders.fulfillment.update');

        Route::get('/orders/{order}/invoice', [AdminOrderController::class, 'invoice'])
            ->middleware('permission:manage_orders')
            ->name('orders.invoice');

        Route::get('/licenses', [AdminLicenseController::class, 'index'])
            ->middleware('permission:manage_licenses')
            ->name('licenses.index');

        Route::get('/licenses/{license}', [AdminLicenseController::class, 'show'])
            ->middleware('permission:manage_licenses')
            ->name('licenses.show');

        Route::get('/downloads', [AdminDownloadController::class, 'index'])
            ->middleware('permission:manage_downloads')
            ->name('downloads.index');

        Route::get('/downloads/{download}', [AdminDownloadController::class, 'show'])
            ->middleware('permission:manage_downloads')
            ->name('downloads.show');

        Route::get('/blog-posts/image-library', [AdminBlogPostController::class, 'imageLibrary'])
            ->middleware('permission:manage_blog_posts')
            ->name('blog-posts.image-library');

        Route::resource('blog-posts', AdminBlogPostController::class)
            ->middleware('permission:manage_blog_posts');

        Route::post('/blog-posts/upload-content-image', [AdminBlogPostController::class, 'uploadContentImage'])
            ->middleware('permission:manage_blog_posts')
            ->name('blog-posts.upload-content-image');
    });

Route::middleware(['auth', 'verified', 'permission:manage_comments'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/comments', [CommentModerationController::class, 'index'])
            ->name('comments.index');

        Route::get('/comments/reports', [CommentModerationController::class, 'reports'])
            ->name('comments.reports');

        Route::patch('/comments/{comment}/approve', [CommentModerationController::class, 'approve'])
            ->name('comments.approve');

        Route::patch('/comments/{comment}/hide', [CommentModerationController::class, 'hide'])
            ->name('comments.hide');

        Route::patch('/comments/{comment}/restore', [CommentModerationController::class, 'restore'])
            ->name('comments.restore');

        Route::patch('/comments/{comment}/pin', [CommentModerationController::class, 'pin'])
            ->name('comments.pin');

        Route::patch('/comments/{comment}/unpin', [CommentModerationController::class, 'unpin'])
            ->name('comments.unpin');

        Route::patch('/comments/{comment}/spam', [CommentModerationController::class, 'spam'])
            ->name('comments.spam');

        Route::delete('/comments/{comment}', [CommentModerationController::class, 'destroy'])
            ->name('comments.destroy');

        Route::patch('/comment-reports/{commentReport}/dismiss', [CommentModerationController::class, 'dismissReport'])
            ->name('comment-reports.dismiss');

        Route::patch('/comment-reports/{commentReport}/reviewed', [CommentModerationController::class, 'markReportReviewed'])
            ->name('comment-reports.reviewed');
    });