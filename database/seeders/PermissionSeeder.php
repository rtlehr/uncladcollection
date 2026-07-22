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