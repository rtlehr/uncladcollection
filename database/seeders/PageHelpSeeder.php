<?php

namespace Database\Seeders;

use App\Enums\PageHelpAudience;
use App\Models\PageHelp;
use App\Services\PageHelp\PageHelpTransferService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class PageHelpSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/page-help.json');

        if (File::exists($path)) {
            app(PageHelpTransferService::class)->importJson(
                File::get($path),
                mode: 'merge',
            );

            $this->command?->info('Page Help content restored from database/seeders/data/page-help.json.');
            return;
        }

        foreach ($this->defaults() as [$key, $title, $body]) {
            PageHelp::updateOrCreate(
                ['page_key' => $key, 'title' => $title, 'audience' => PageHelpAudience::Admin->value, 'sort_order' => 0],
                [
                    'summary' => $body,
                    'content' => '<p>'.$body.'</p>',
                    'is_active' => true,
                    'is_published' => true,
                    'published_at' => now(),
                ],
            );
        }

        $this->command?->warn('No Page Help export was found. Seeded the built-in starter help only.');
    }

    private function defaults(): array
    {
        return [
            ['admin.dashboard', 'Admin overview help', 'Use this dashboard to monitor the major operational areas of Unclad Collection.'],
            ['admin.support.dashboard', 'Support dashboard help', 'Review ticket workload, response queues, priorities, and assignments.'],
            ['admin.page-help.index', 'Page Help administration', 'Create and maintain contextual documentation associated with registered page keys.'],
        ];
    }
}
