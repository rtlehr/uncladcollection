<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [

            [
                'name' => 'admin',
                'label' => 'Administrator',
                'description' => 'Full access to all features.',
            ],

            [
                'name' => 'editor',
                'label' => 'Editor',
                'description' => 'Manage content and images.',
            ],

            [
                'name' => 'moderator',
                'label' => 'Moderator',
                'description' => 'Moderate comments and community content.',
            ],

            [
                'name' => 'contributor',
                'label' => 'Contributor',
                'description' => 'Submit images and articles.',
            ],

            [
                'name' => 'member',
                'label' => 'Member',
                'description' => 'Standard registered member.',
            ],
        ];

        foreach ($roles as $roleData) {

            $role = Role::updateOrCreate(
                ['name' => $roleData['name']],
                array_merge($roleData, [
                    'is_system' => true,
                    'is_locked' => true,
                ])
            );

            if ($role->name === 'admin') {
                $role->permissions()->sync(
                    Permission::pluck('id')->toArray()
                );
            }

            if ($role->name === 'editor') {
                $role->permissions()->sync(
                    Permission::whereIn('name', [
                        'view_admin',
                        'manage_images',
                        'manage_categories',
                        'manage_tags',
                        'manage_blog_posts',
                        'manage_ai_content',
                        'manage_public_pages',
                        'publish_public_pages',
                    ])->pluck('id')->toArray()
                );
            }

            if ($role->name === 'moderator') {
                $role->permissions()->sync(
                    Permission::whereIn('name', [
                        'view_admin',
                        'moderate_comments',
                    ])->pluck('id')->toArray()
                );
            }
        }
    }
}