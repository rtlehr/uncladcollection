<?php

namespace App\Console\Commands;

use Database\Seeders\AdvertisingPipelineDemoSeeder;
use App\Models\Advertiser;
use App\Models\AnalyticsEvent;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\SponsorshipPackage;
use App\Models\SponsorshipLead;
use App\Models\SponsorshipProposal;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleanupAdvertisingPipelineDemo extends Command
{
    protected $signature = 'advertising:demo-cleanup {--force : Delete without confirmation}';
    protected $description = 'Remove records and media created by AdvertisingPipelineDemoSeeder.';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Remove the complete advertising pipeline demo?')) {
            $this->info('Cleanup canceled.');
            return self::SUCCESS;
        }

        DB::transaction(function (): void {
            AnalyticsEvent::query()->where('source', AdvertisingPipelineDemoSeeder::DEMO_PREFIX)->delete();

            BlogPost::withTrashed()->where('slug', 'how-sponsored-advertising-appears-on-unclad-collection-demo')->forceDelete();
            Category::query()->where('slug', 'site-guides-demo')->delete();
            Tag::query()->where('slug', 'advertising-demo')->where('tag_type', 'blog')->delete();

            SponsorshipProposal::query()->where('proposal_number', 'DEMO-PROP-0001')->delete();
            SponsorshipLead::query()->where('company_name', 'SunHaven Naturist Resort (Demo)')->delete();
            SponsorshipPackage::query()->where('code', 'monthly-brand-partner-demo')->delete();
            Advertiser::withTrashed()->where('slug', 'sunhaven-naturist-resort-demo')->forceDelete();
            User::query()->where('email', 'advertiser@sunhaven-demo.test')->delete();
        });

        Storage::disk('public')->deleteDirectory('advertising/demo/sunhaven');
        $this->info('Advertising pipeline demo removed.');

        return self::SUCCESS;
    }
}
