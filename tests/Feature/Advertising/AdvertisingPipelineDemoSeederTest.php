<?php

namespace Tests\Feature\Advertising;

use App\Models\AdCreative;
use App\Models\Advertiser;
use App\Models\AdvertisingCampaign;
use App\Models\AdvertisingInvoice;
use App\Models\AnalyticsEvent;
use App\Models\BlogPost;
use App\Models\SponsorshipProposal;
use Database\Seeders\AdvertisingPipelineDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdvertisingPipelineDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_complete_repeatable_pipeline(): void
    {
        Storage::fake('public');

        $this->seed(AdvertisingPipelineDemoSeeder::class);
        $this->seed(AdvertisingPipelineDemoSeeder::class);

        $advertiser = Advertiser::query()->where('slug', 'sunhaven-naturist-resort-demo')->firstOrFail();
        $campaign = AdvertisingCampaign::query()->where('public_code', 'sunhaven-summer-demo')->firstOrFail();
        $proposal = SponsorshipProposal::query()->where('proposal_number', 'DEMO-PROP-0001')->firstOrFail();
        $invoice = AdvertisingInvoice::query()->where('invoice_number', 'DEMO-ADV-0001')->firstOrFail();
        $post = BlogPost::query()->where('slug', 'how-sponsored-advertising-appears-on-unclad-collection-demo')->firstOrFail();

        $this->assertSame('converted', $proposal->status);
        $this->assertNotNull($proposal->acceptance);
        $this->assertSame($campaign->id, $proposal->converted_campaign_id);
        $this->assertSame('paid', $invoice->status);
        $this->assertSame(0, $invoice->balance_cents);
        $this->assertTrue($post->isPublished());
        $this->assertSame(4, AdCreative::query()->where('advertising_campaign_id', $campaign->id)->where('status', 'approved')->count());
        $this->assertGreaterThan(0, AnalyticsEvent::query()->where('source', AdvertisingPipelineDemoSeeder::DEMO_PREFIX)->count());
        $this->assertSame(1, Advertiser::query()->where('slug', 'sunhaven-naturist-resort-demo')->count());
        $this->assertSame($advertiser->id, $campaign->advertiser_id);

        foreach (AdCreative::query()->where('advertising_campaign_id', $campaign->id)->get() as $creative) {
            Storage::disk('public')->assertExists($creative->media_path);
        }
    }
}
