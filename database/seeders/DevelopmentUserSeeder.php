<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [

            [
                'name' => 'System Administrator',
                'username' => 'admin',
                'email' => 'admin@uncladcollection.local',
                'role' => 'admin',
            ],

            [
                'name' => 'Content Editor',
                'username' => 'editor1',
                'email' => 'editor1@uncladcollection.local',
                'role' => 'editor',
            ],

            [
                'name' => 'Site Moderator',
                'username' => 'moderator1',
                'email' => 'moderator1@uncladcollection.local',
                'role' => 'moderator',
            ],

            [
                'name' => 'Image Contributor',
                'username' => 'contributor1',
                'email' => 'contributor1@uncladcollection.local',
                'role' => 'contributor',
            ],

            [
                'name' => 'Member One',
                'username' => 'member1',
                'email' => 'member1@uncladcollection.local',
                'role' => 'member',
            ],

            [
                'name' => 'Member Two',
                'username' => 'member2',
                'email' => 'member2@uncladcollection.local',
                'role' => 'member',
            ],
        ];

        foreach ($users as $userData) {

            $role = Role::where('name', $userData['role'])->first();

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ]
            );

            if ($role) {
                $user->roles()->sync([$role->id]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Direct Permission Test Users
        |--------------------------------------------------------------------------
        */

        $blogUser = User::updateOrCreate(
            ['email' => 'blogspecial@uncladcollection.local'],
            [
                'name' => 'Blog Special User',
                'username' => 'special_blog_user',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $blogUser->roles()->sync([
            Role::where('name', 'member')->first()?->id,
        ]);

        $blogPermission = Permission::where(
            'name',
            'manage_blog_posts'
        )->first();

        if ($blogPermission) {
            $blogUser->permissions()->sync([
                $blogPermission->id,
            ]);
        }

        $imageUser = User::updateOrCreate(
            ['email' => 'imagespecial@uncladcollection.local'],
            [
                'name' => 'Image Special User',
                'username' => 'special_image_user',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $imageUser->roles()->sync([
            Role::where('name', 'member')->first()?->id,
        ]);

        $imagePermission = Permission::where(
            'name',
            'manage_images'
        )->first();

        if ($imagePermission) {
            $imageUser->permissions()->sync([
                $imagePermission->id,
            ]);
        }
    }
}