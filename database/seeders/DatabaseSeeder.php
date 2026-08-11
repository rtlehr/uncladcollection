<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment(['local', 'testing'])) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        $this->call([
            SiteSettingSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            EmailTemplateSeeder::class,
            PageHelpSeeder::class,
            PublicPageSeeder::class,
            MessageBoxSeeder::class,
            DevelopmentUserSeeder::class,

            CategorySeeder::class,
            TagSeeder::class,
            CollectionSeeder::class,
            LicenseTypeSeeder::class,
            AssetConfigurationTemplateSeeder::class,
            SupportTicketCategorySeeder::class,

            AiKeywordExclusionSeeder::class,
            AiProviderSeeder::class,
            AiContentStudioSeeder::class,

            AdvertisingPipelineDemoSeeder::class,
            AdPlacementSeeder::class,

        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->call(DevelopmentAssetSeeder::class);
        }
    }
}