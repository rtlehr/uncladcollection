<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            // Administration
            [
                'group_name' => 'Administration',
                'name' => 'view_admin',
                'label' => 'View Admin Area',
                'description' => 'Access the admin section.',
            ],
            [
                'group_name' => 'Administration',
                'name' => 'manage_users',
                'label' => 'Manage Users',
                'description' => 'Create, edit, and delete users.',
            ],
            [
                'group_name' => 'Administration',
                'name' => 'manage_roles',
                'label' => 'Manage Roles',
                'description' => 'Manage security roles.',
            ],
            [
                'group_name' => 'Administration',
                'name' => 'manage_permissions',
                'label' => 'Manage Permissions',
                'description' => 'Manage permissions.',
            ],
            [
                'group_name' => 'Administration',
                'name' => 'manage_site_settings',
                'label' => 'Manage Site Settings',
                'description' => 'Manage site configuration.',
            ],

            // Content
            [
                'name' => 'manage_categories',
                'label' => 'Manage Categories',
                'group_name' => 'Content',
                'description' => 'Create, edit, and delete categories.',
            ],
            [
                'name' => 'manage_tags',
                'label' => 'Manage Tags',
                'group_name' => 'Content',
                'description' => 'Create, edit, delete, and manage tags.',
            ],
            [
                'name' => 'manage_collections',
                'label' => 'Manage Collections',
                'group_name' => 'Content',
                'description' => 'Create, edit, delete, and manage image collections.',
            ],


            // Images
            [
                'group_name' => 'Images',
                'name' => 'manage_images',
                'label' => 'Manage Images',
                'description' => 'Manage stock images.',
            ],
            [
                'group_name' => 'Images',
                'name' => 'approve_images',
                'label' => 'Approve Images',
                'description' => 'Approve submitted images.',
            ],

            // Categories
            [
                'group_name' => 'Content',
                'name' => 'manage_categories',
                'label' => 'Manage Categories',
                'description' => 'Manage categories.',
            ],
            [
                'group_name' => 'Content',
                'name' => 'manage_tags',
                'label' => 'Manage Tags',
                'description' => 'Manage tags.',
            ],

            // Public Pages
            ['group_name' => 'Content', 'name' => 'manage_public_pages', 'label' => 'Manage Public Pages', 'description' => 'Create, edit, and remove public informational pages.'],
            ['group_name' => 'Content', 'name' => 'publish_public_pages', 'label' => 'Publish Public Pages', 'description' => 'Publish and unpublish public informational pages.'],

            // Blog
            [
                'group_name' => 'Blog',
                'name' => 'manage_blog_posts',
                'label' => 'Manage Blog Posts',
                'description' => 'Manage blog content.',
            ],

            // Comments
            [
                'group_name' => 'Community',
                'name' => 'moderate_comments',
                'label' => 'Moderate Comments',
                'description' => 'Approve and moderate comments.',
            ],

            // Orders
            [
                'group_name' => 'Commerce',
                'name' => 'manage_orders',
                'label' => 'Manage Orders',
                'description' => 'Manage customer orders.',
            ],

            // Sponsorship & Advertising
            ['group_name'=>'Advertising','name'=>'view_advertising','label'=>'View Advertising','description'=>'View sponsorship and advertising administration.'],
            ['group_name'=>'Advertising','name'=>'manage_advertisers','label'=>'Manage Advertisers','description'=>'Manage advertiser organizations and contacts.'],
            ['group_name'=>'Advertising','name'=>'manage_ad_campaigns','label'=>'Manage Ad Campaigns','description'=>'Create and manage paid advertising campaigns.'],
            ['group_name'=>'Advertising','name'=>'approve_ad_campaigns','label'=>'Approve Ad Campaigns','description'=>'Approve or reject advertising campaigns and creatives.'],
            ['group_name'=>'Advertising','name'=>'manage_ad_placements','label'=>'Manage Ad Placements','description'=>'Manage advertising inventory and placement rules.'],
            ['group_name'=>'Advertising','name'=>'view_advertising_reports','label'=>'View Advertising Reports','description'=>'View sponsorship and advertising performance reports.'],
            ['group_name'=>'Advertising','name'=>'view_advertising_billing','label'=>'View Advertising Billing','description'=>'View advertiser invoices and payment history.'],
            ['group_name'=>'Advertising','name'=>'manage_advertising_invoices','label'=>'Manage Advertising Invoices','description'=>'Create, issue, edit, and void advertising invoices.'],
            ['group_name'=>'Advertising','name'=>'record_advertising_payments','label'=>'Record Advertising Payments','description'=>'Record manual and Stripe advertising payments.'],
            ['group_name'=>'Advertising','name'=>'refund_advertising_payments','label'=>'Refund Advertising Payments','description'=>'Record and reconcile advertising refunds.'],


            ['group_name'=>'Sponsorship Sales','name'=>'view_sponsorship_sales','label'=>'View Sponsorship Sales','description'=>'View sponsorship packages, pipeline, proposals, and inventory.'],
            ['group_name'=>'Sponsorship Sales','name'=>'manage_sponsorship_packages','label'=>'Manage Sponsorship Packages','description'=>'Create and maintain reusable sponsorship offerings.'],
            ['group_name'=>'Sponsorship Sales','name'=>'manage_sponsorship_leads','label'=>'Manage Sponsorship Leads','description'=>'Manage sponsorship leads and sales activities.'],
            ['group_name'=>'Sponsorship Sales','name'=>'manage_sponsorship_proposals','label'=>'Manage Sponsorship Proposals','description'=>'Create and manage sponsorship proposals.'],
            ['group_name'=>'Sponsorship Sales','name'=>'manage_ad_inventory','label'=>'Manage Ad Inventory','description'=>'Review and manage placement availability and reservations.'],
            ['group_name'=>'Sponsorship Sales','name'=>'convert_sponsorship_proposals','label'=>'Convert Sponsorship Proposals','description'=>'Convert accepted proposals into campaigns and invoices.'],

            // Support
            ['group_name' => 'Support', 'name' => 'view_support_tickets', 'label' => 'View Support Tickets', 'description' => 'Access the internal support ticket workspace.'],
            ['group_name' => 'Support', 'name' => 'reply_support_tickets', 'label' => 'Reply to Support Tickets', 'description' => 'Send customer-visible staff replies.'],
            ['group_name' => 'Support', 'name' => 'assign_support_tickets', 'label' => 'Assign Support Tickets', 'description' => 'Assign and reassign support tickets.'],
            ['group_name' => 'Support', 'name' => 'manage_support_tickets', 'label' => 'Manage Support Tickets', 'description' => 'Change ticket category, priority, related records, and operational details.'],
            ['group_name' => 'Support', 'name' => 'add_support_internal_notes', 'label' => 'Add Support Internal Notes', 'description' => 'Add staff-only notes to support tickets.'],
            ['group_name' => 'Support', 'name' => 'resolve_support_tickets', 'label' => 'Resolve Support Tickets', 'description' => 'Resolve, close, cancel, and reopen support tickets.'],
            ['group_name' => 'Support', 'name' => 'manage_support_categories', 'label' => 'Manage Support Categories', 'description' => 'Manage support ticket categories and defaults.'],
            ['group_name' => 'Support', 'name' => 'view_support_reports', 'label' => 'View Support Reports', 'description' => 'View support workload and performance reporting.'],

            // Page Help
            ['group_name' => 'Page Help', 'name' => 'view_page_help_admin', 'label' => 'View Page Help Administration', 'description' => 'View Page Help entries and coverage.'],
            ['group_name' => 'Page Help', 'name' => 'manage_page_help', 'label' => 'Manage Page Help', 'description' => 'Create, edit, and delete Page Help content.'],
            ['group_name' => 'Page Help', 'name' => 'publish_page_help', 'label' => 'Publish Page Help', 'description' => 'Publish and unpublish Page Help content.'],

            [
                'group_name' => 'Administration',
                'name' => 'impersonate_users',
                'label' => 'Impersonate Users',
                'description' => 'Temporarily view the public customer experience as a non-administrative user.',
            ],

            // Reports
            [
                'group_name' => 'Reports',
                'name' => 'view_reports',
                'label' => 'View Reports',
                'description' => 'Access reporting.',
            ],
            [
                'name' => 'manage_license_types',
                'group_name' => 'Licensing',
                'label' => 'Manage License Types',
                'description' => 'Create, edit, and delete license types.',
            ],
            [
                'name' => 'manage_orders',
                'group_name' => 'Licensing',
                'label' => 'Manage Orders',
                'description' => 'View and manage customer orders.',
            ],
            [
                'name' => 'manage_licenses',
                'group_name' => 'Licensing',
                'label' => 'Manage Licenses',
                'description' => 'View and manage customer image licenses.',
            ],
            [
                'name' => 'manage_downloads',
                'group_name' => 'Licensing',
                'label' => 'Manage Downloads',
                'description' => 'View customer image download history.',
            ],
            [
                'name' => 'manage_blog_posts',
                'group_name' => 'Blog',
                'label' => 'Manage Blog Posts',
                'description' => 'Manage blog content.',
            ],
            [
                'name' => 'manage_comments',
                'group_name' => 'Comments',
                'label' => 'Manage comments',
                'description' => 'Manage comments',
            ],

        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                array_merge($permission, [
                    'is_system' => true,
                    'is_locked' => true,
                ])
            );
        }
    }
}