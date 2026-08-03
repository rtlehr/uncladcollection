<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            SiteSettingSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            EmailTemplateSeeder::class,
            PageHelpSeeder::class,
            PublicPageSeeder::class,
            DevelopmentUserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            AiKeywordExclusionSeeder::class,
            AiContentStudioSeeder::class,
            CollectionSeeder::class,
            LicenseTypeSeeder::class,
            AssetConfigurationTemplateSeeder::class,
            SupportTicketCategorySeeder::class,
        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->call(DevelopmentAssetSeeder::class);
        }
    }
}
