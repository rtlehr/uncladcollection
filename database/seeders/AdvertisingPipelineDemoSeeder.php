<?php

namespace Database\Seeders;

use App\Enums\AnalyticsEventName;
use App\Enums\FinancialTransactionStatus;
use App\Enums\FinancialTransactionType;
use App\Models\AdCreative;
use App\Models\AdInventoryReservation;
use App\Models\AdPlacement;
use App\Models\Advertiser;
use App\Models\AdvertiserMembership;
use App\Models\AdvertisingCampaign;
use App\Models\AdvertisingInvoice;
use App\Models\AdvertisingInvoiceItem;
use App\Models\AdvertisingPayment;
use App\Models\AnalyticsEvent;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\FinancialTransaction;
use App\Models\SalesActivity;
use App\Models\SponsorshipLead;
use App\Models\SponsorshipPackage;
use App\Models\SponsorshipProposal;
use App\Models\SponsorshipProposalAcceptance;
use App\Models\SponsorshipProposalItem;
use App\Models\SponsorshipProposalStatusHistory;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class AdvertisingPipelineDemoSeeder extends Seeder
{
    public const DEMO_PREFIX = 'demo-sunhaven';

    public function run(): void
    {
        $this->call(AdPlacementSeeder::class);

        $admin = User::query()->where('email', 'admin@uncladcollection.test')->first()
            ?? User::query()->whereNotNull('email_verified_at')->first()
            ?? User::factory()->create([
                'name' => 'Demo Advertising Administrator',
                'email' => 'admin@uncladcollection.test',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]);

        $advertiserUser = User::query()->updateOrCreate(
            ['email' => 'advertiser@sunhaven-demo.test'],
            [
                'name' => 'Jordan Ellis',
                'username' => 'sunhaven-demo',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'is_disabled' => false,
            ]
        );

        $advertiser = Advertiser::withTrashed()->updateOrCreate(
            ['slug' => 'sunhaven-naturist-resort-demo'],
            [
                'uuid' => $this->stableUuid('advertiser'),
                'name' => 'SunHaven Naturist Resort (Demo)',
                'status' => 'active',
                'website_url' => 'https://example.com/sunhaven-demo',
                'billing_email' => 'billing@sunhaven-demo.test',
                'contact_name' => 'Jordan Ellis',
                'contact_email' => 'advertiser@sunhaven-demo.test',
                'contact_phone' => '555-010-2200',
                'billing_address' => "100 Sunshine Way\nDemo Springs, VA 00000",
                'notes' => 'Demo advertiser created by AdvertisingPipelineDemoSeeder.',
                'deleted_at' => null,
            ]
        );

        AdvertiserMembership::query()->updateOrCreate(
            ['advertiser_id' => $advertiser->id, 'user_id' => $advertiserUser->id],
            [
                'role' => 'owner',
                'is_primary' => true,
                'is_active' => true,
                'invited_at' => now()->subDays(45),
                'accepted_at' => now()->subDays(44),
                'invited_by' => $admin->id,
            ]
        );

        $placements = AdPlacement::query()->whereIn('code', [
            'homepage-below-hero',
            'asset-gallery-inline',
            'blog-index-inline',
            'blog-article-after-content',
        ])->get()->keyBy('code');

        $package = SponsorshipPackage::query()->updateOrCreate(
            ['code' => 'monthly-brand-partner-demo'],
            [
                'uuid' => $this->stableUuid('package'),
                'name' => 'Monthly Brand Partner (Demo)',
                'description' => 'A complete monthly sponsorship example spanning homepage, marketplace, and editorial placements.',
                'duration_days' => 30,
                'base_price_cents' => 300000,
                'package_price_cents' => 250000,
                'impression_goal' => 50000,
                'click_goal' => 750,
                'included_creatives' => 4,
                'billing_terms' => 'Due on receipt',
                'is_active' => true,
                'internal_notes' => 'Demo package. Safe to remove with advertising:demo-cleanup.',
            ]
        );

        $package->placements()->sync(collect($placements)->mapWithKeys(
            fn (AdPlacement $placement) => [$placement->id => ['quantity' => 1, 'priority' => 80]]
        )->all());

        $lead = SponsorshipLead::query()->updateOrCreate(
            ['uuid' => $this->stableUuid('lead')],
            [
                'advertiser_id' => $advertiser->id,
                'assigned_to' => $admin->id,
                'company_name' => $advertiser->name,
                'contact_name' => 'Jordan Ellis',
                'contact_email' => 'advertiser@sunhaven-demo.test',
                'contact_phone' => '555-010-2200',
                'source' => 'Demo referral',
                'stage' => 'won',
                'estimated_value_cents' => 250000,
                'probability' => 100,
                'target_close_date' => today()->subDays(21),
                'next_follow_up_at' => null,
                'notes' => 'Complete demonstration of the sponsorship sales pipeline.',
                'won_at' => now()->subDays(20),
                'lost_at' => null,
                'lost_reason' => null,
            ]
        );

        $activities = [
            ['type' => 'email', 'subject' => 'Introduction and media kit', 'details' => 'Shared the Unclad Collection sponsorship overview.', 'days' => 35],
            ['type' => 'call', 'subject' => 'Discovery call', 'details' => 'Reviewed audience, goals, placements, and creative requirements.', 'days' => 31],
            ['type' => 'proposal', 'subject' => 'Proposal sent', 'details' => 'Sent the Monthly Brand Partner proposal through the advertiser portal.', 'days' => 25],
            ['type' => 'note', 'subject' => 'Proposal accepted', 'details' => 'Advertiser electronically accepted the proposal.', 'days' => 22],
        ];
        foreach ($activities as $activity) {
            SalesActivity::query()->updateOrCreate(
                ['sponsorship_lead_id' => $lead->id, 'subject' => $activity['subject']],
                [
                    'user_id' => $admin->id,
                    'type' => $activity['type'],
                    'details' => $activity['details'],
                    'occurred_at' => now()->subDays($activity['days']),
                ]
            );
        }

        $proposal = SponsorshipProposal::query()->updateOrCreate(
            ['proposal_number' => 'DEMO-PROP-0001'],
            [
                'uuid' => $this->stableUuid('proposal'),
                'sponsorship_lead_id' => $lead->id,
                'advertiser_id' => $advertiser->id,
                'sponsorship_package_id' => $package->id,
                'created_by' => $admin->id,
                'title' => 'SunHaven Summer Escape Sponsorship (Demo)',
                'status' => 'converted',
                'starts_on' => today()->subDays(7),
                'ends_on' => today()->addDays(23),
                'expires_on' => today()->subDays(18),
                'currency' => 'USD',
                'subtotal_cents' => 250000,
                'discount_cents' => 0,
                'tax_cents' => 0,
                'total_cents' => 250000,
                'terms' => 'Thirty-day campaign. Placement availability and approved creatives are required before delivery.',
                'notes' => 'Demo accepted proposal used to demonstrate the complete pipeline.',
                'sent_at' => now()->subDays(25),
                'accepted_at' => now()->subDays(22),
                'declined_at' => null,
            ]
        );

        $proposal->items()->delete();
        foreach ($placements as $placement) {
            SponsorshipProposalItem::query()->create([
                'sponsorship_proposal_id' => $proposal->id,
                'ad_placement_id' => $placement->id,
                'description' => $placement->name.' sponsorship placement',
                'billing_model' => 'sponsorship',
                'quantity' => 1,
                'unit_amount_cents' => $placement->code === 'homepage-below-hero' ? 100000 : 50000,
                'line_total_cents' => $placement->code === 'homepage-below-hero' ? 100000 : 50000,
                'metadata' => ['demo' => true, 'placement_code' => $placement->code],
            ]);
        }

        SponsorshipProposalAcceptance::query()->updateOrCreate(
            ['sponsorship_proposal_id' => $proposal->id],
            [
                'user_id' => $advertiserUser->id,
                'signer_name' => 'Jordan Ellis',
                'signer_title' => 'Marketing Director',
                'signer_email' => 'advertiser@sunhaven-demo.test',
                'signer_company' => 'SunHaven Naturist Resort',
                'terms_acknowledged' => true,
                'accepted_at' => now()->subDays(22),
                'ip_address' => '192.0.2.10',
                'user_agent' => 'Advertising Pipeline Demo Seeder',
            ]
        );

        SponsorshipProposalStatusHistory::query()->where('sponsorship_proposal_id', $proposal->id)->delete();
        foreach ([
            ['from' => 'draft', 'to' => 'sent', 'days' => 25, 'source' => 'admin'],
            ['from' => 'sent', 'to' => 'accepted', 'days' => 22, 'source' => 'advertiser_portal'],
            ['from' => 'accepted', 'to' => 'converted', 'days' => 20, 'source' => 'admin'],
        ] as $history) {
            SponsorshipProposalStatusHistory::query()->create([
                'sponsorship_proposal_id' => $proposal->id,
                'user_id' => $history['source'] === 'advertiser_portal' ? $advertiserUser->id : $admin->id,
                'from_status' => $history['from'],
                'to_status' => $history['to'],
                'reason' => 'Demo pipeline status transition.',
                'source' => $history['source'],
                'ip_address' => '192.0.2.10',
                'user_agent' => 'Advertising Pipeline Demo Seeder',
                'created_at' => now()->subDays($history['days']),
                'updated_at' => now()->subDays($history['days']),
            ]);
        }

        $campaign = AdvertisingCampaign::withTrashed()->updateOrCreate(
            ['public_code' => 'sunhaven-summer-demo'],
            [
                'uuid' => $this->stableUuid('campaign'),
                'advertiser_id' => $advertiser->id,
                'name' => 'SunHaven Summer Escape (Demo)',
                'status' => 'active',
                'objective' => 'awareness',
                'pricing_model' => 'sponsorship',
                'budget_cents' => 250000,
                'contract_value_cents' => 250000,
                'impression_goal' => 50000,
                'click_goal' => 750,
                'starts_at' => now()->subDays(7),
                'ends_at' => now()->addDays(23),
                'submitted_at' => now()->subDays(19),
                'approved_at' => now()->subDays(18),
                'approved_by' => $admin->id,
                'rejection_reason' => null,
                'internal_notes' => 'Demo campaign created from DEMO-PROP-0001.',
                'deleted_at' => null,
            ]
        );

        $campaign->placements()->sync(collect($placements)->mapWithKeys(
            fn (AdPlacement $placement) => [$placement->id => ['priority' => 80, 'allocated_budget_cents' => 62500]]
        )->all());

        $proposal->forceFill([
            'converted_campaign_id' => $campaign->id,
            'converted_at' => now()->subDays(20),
        ])->save();

        foreach ($placements as $placement) {
            AdInventoryReservation::query()->updateOrCreate(
                ['uuid' => $this->stableUuid('reservation-'.$placement->code)],
                [
                    'ad_placement_id' => $placement->id,
                    'advertising_campaign_id' => $campaign->id,
                    'sponsorship_proposal_id' => $proposal->id,
                    'status' => 'committed',
                    'starts_on' => today()->subDays(7),
                    'ends_on' => today()->addDays(23),
                    'hold_expires_at' => null,
                    'quantity' => 1,
                    'notes' => 'Demo committed inventory reservation.',
                ]
            );
        }

        $invoice = AdvertisingInvoice::query()->updateOrCreate(
            ['invoice_number' => 'DEMO-ADV-0001'],
            [
                'uuid' => $this->stableUuid('invoice'),
                'advertiser_id' => $advertiser->id,
                'advertising_campaign_id' => $campaign->id,
                'status' => 'paid',
                'currency' => 'USD',
                'subtotal_cents' => 250000,
                'discount_cents' => 0,
                'tax_cents' => 0,
                'total_cents' => 250000,
                'paid_cents' => 250000,
                'refunded_cents' => 0,
                'balance_cents' => 0,
                'issued_at' => today()->subDays(20),
                'due_at' => today()->subDays(13),
                'paid_at' => now()->subDays(17),
                'notes' => 'Demo paid sponsorship invoice.',
            ]
        );

        $invoice->items()->delete();
        AdvertisingInvoiceItem::query()->create([
            'advertising_invoice_id' => $invoice->id,
            'description' => 'Monthly Brand Partner sponsorship package',
            'billing_model' => 'sponsorship',
            'quantity' => 1,
            'unit_amount_cents' => 250000,
            'line_total_cents' => 250000,
            'metadata' => ['demo' => true, 'proposal_number' => $proposal->proposal_number],
        ]);

        $payment = AdvertisingPayment::query()->updateOrCreate(
            ['provider_reference' => 'DEMO-PAYMENT-0001'],
            [
                'uuid' => $this->stableUuid('payment'),
                'advertising_invoice_id' => $invoice->id,
                'type' => 'payment',
                'status' => 'succeeded',
                'provider' => 'manual',
                'amount_cents' => 250000,
                'currency' => 'USD',
                'notes' => 'Demo payment recorded in full.',
                'metadata' => ['demo' => true],
                'processed_at' => now()->subDays(17),
            ]
        );

        FinancialTransaction::query()->updateOrCreate(
            ['provider' => 'demo', 'provider_reference' => 'DEMO-PAYMENT-0001'],
            [
                'order_id' => null,
                'advertising_invoice_id' => $invoice->id,
                'advertising_payment_id' => $payment->id,
                'type' => FinancialTransactionType::Payment,
                'status' => FinancialTransactionStatus::Succeeded,
                'amount_cents' => 250000,
                'currency' => 'USD',
                'reason' => 'Advertising sponsorship payment',
                'notes' => 'Demo financial reconciliation record.',
                'metadata' => ['demo' => true],
                'occurred_at' => now()->subDays(17),
            ]
        );

        $proposal->forceFill(['converted_invoice_id' => $invoice->id])->save();

        $this->seedCreatives($campaign, $placements, $admin);
        $blogPost = $this->seedBlogPost($admin);
        $this->seedAnalytics($campaign, $placements, $advertiserUser, $blogPost);

        $this->command?->info('Advertising pipeline demo seeded successfully.');
        $this->command?->line('Advertiser portal login: advertiser@sunhaven-demo.test / password');
        $this->command?->line('Demo blog post: /blog/how-sponsored-advertising-appears-on-unclad-collection-demo');
        $this->command?->line('Cleanup: php artisan advertising:demo-cleanup');
    }

    private function seedCreatives(AdvertisingCampaign $campaign, $placements, User $admin): void
    {
        foreach ($placements as $placement) {
            $directory = 'advertising/demo/sunhaven/'.$placement->code;
            $path = $directory.'/rendered.svg';
            $originalPath = $directory.'/original.svg';
            $svg = $this->svg((int) $placement->width, (int) $placement->height, $placement->name);
            Storage::disk('public')->put($path, $svg);
            Storage::disk('public')->put($originalPath, $svg);

            AdCreative::withTrashed()->updateOrCreate(
                [
                    'advertising_campaign_id' => $campaign->id,
                    'ad_placement_id' => $placement->id,
                    'name' => 'SunHaven '.$placement->name.' (Demo)',
                ],
                [
                    'uuid' => $this->stableUuid('creative-'.$placement->code),
                    'creative_type' => 'image',
                    'status' => 'approved',
                    'media_path' => $path,
                    'original_media_path' => $originalPath,
                    'media_edit_data' => [
                        'demo' => true,
                        'crop' => ['x' => 0, 'y' => 0, 'width' => $placement->width, 'height' => $placement->height],
                        'placement_code' => $placement->code,
                    ],
                    'mime_type' => 'image/svg+xml',
                    'original_filename' => 'sunhaven-'.$placement->code.'.svg',
                    'file_size' => strlen($svg),
                    'width' => $placement->width,
                    'height' => $placement->height,
                    'headline' => 'Find Your Natural Escape',
                    'body' => 'Discover a welcoming resort experience designed around comfort, nature, and community.',
                    'cta_label' => 'Explore SunHaven',
                    'destination_url' => 'https://example.com/sunhaven-demo',
                    'alt_text' => 'Sponsored message from SunHaven Naturist Resort inviting visitors to explore a natural resort escape.',
                    'submitted_at' => now()->subDays(19),
                    'approved_at' => now()->subDays(18),
                    'approved_by' => $admin->id,
                    'rejection_reason' => null,
                    'deleted_at' => null,
                ]
            );
        }
    }

    private function seedBlogPost(User $admin): BlogPost
    {
        $post = BlogPost::withTrashed()->updateOrCreate(
            ['slug' => 'how-sponsored-advertising-appears-on-unclad-collection-demo'],
            [
                'user_id' => $admin->id,
                'title' => 'How Sponsored Advertising Appears on Unclad Collection (Demo)',
                'excerpt' => 'A demonstration article showing the correct separation between editorial content and sponsored advertising.',
                'content' => <<<'HTML'
<h2>Editorial content remains independent</h2>
<p>This demonstration article explains the correct way sponsored advertising appears within Unclad Collection. The article body itself contains no advertiser HTML, tracking script, embedded banner, or manually pasted promotional markup.</p>
<p>Editors should write and publish articles normally. Advertising is delivered by the site&rsquo;s placement system, which keeps editorial content separate from paid messages.</p>
<h2>Where the advertisement appears</h2>
<p>On the public article page, an approved advertisement may appear after the article content. The page template renders the reusable <strong>blog-article-after-content</strong> placement. If there is no eligible active campaign, the placement produces no empty box or broken layout.</p>
<h2>Why this approach is correct</h2>
<ul>
<li>Campaign schedules and approvals remain enforced.</li>
<li>Advertisers cannot inject code into editorial content.</li>
<li>Creatives remain placement-compatible and accessible.</li>
<li>Impressions and clicks are measured consistently.</li>
<li>Sponsored content is clearly labeled.</li>
<li>Ads can rotate or stop without editing the article.</li>
</ul>
<h2>Testing this demonstration</h2>
<p>Scroll below this article. When the SunHaven demo campaign is active and its creative is approved, the sponsored banner appears in the standard advertising placement after the content.</p>
HTML,
                'status' => BlogPost::STATUS_PUBLISHED,
                'published_at' => now()->subDays(5),
                'seo_title' => 'Sponsored Advertising Demo | Unclad Collection',
                'seo_description' => 'See how Unclad Collection keeps sponsored advertising separate from editorial blog content.',
                'is_featured' => false,
                'is_active' => true,
                'views_count' => 24,
                'comments_enabled' => true,
                'comments_visible' => true,
                'comments_require_approval' => true,
                'deleted_at' => null,
            ]
        );

        $category = Category::query()->updateOrCreate(
            ['slug' => 'site-guides-demo'],
            ['name' => 'Site Guides (Demo)', 'description' => 'Demonstration and operating guides.', 'category_type' => 'blog', 'sort_order' => 90, 'is_active' => true]
        );
        $tag = Tag::query()->updateOrCreate(
            ['slug' => 'advertising-demo', 'tag_type' => 'blog'],
            ['name' => 'Advertising Demo', 'description' => 'Demo content for the sponsorship and advertising platform.']
        );
        $post->categories()->syncWithoutDetaching([$category->id]);
        $post->tags()->syncWithoutDetaching([$tag->id]);

        return $post;
    }

    private function seedAnalytics(AdvertisingCampaign $campaign, $placements, User $advertiserUser, BlogPost $blogPost): void
    {
        $creatives = AdCreative::query()->where('advertising_campaign_id', $campaign->id)->get()->keyBy('ad_placement_id');
        AnalyticsEvent::query()->where('source', self::DEMO_PREFIX)->delete();

        foreach (range(0, 6) as $daysAgo) {
            foreach ($placements as $placement) {
                $creative = $creatives->get($placement->id);
                if (! $creative) {
                    continue;
                }

                $impressions = 12 + ($daysAgo * 2);
                $clicks = max(1, intdiv($impressions, 6));
                foreach (range(1, $impressions) as $index) {
                    AnalyticsEvent::query()->create([
                        'event_uuid' => (string) Str::uuid(),
                        'event_name' => AnalyticsEventName::AdvertisingImpression,
                        'subject_type' => $creative->getMorphClass(),
                        'subject_id' => $creative->id,
                        'user_id' => $index % 4 === 0 ? $advertiserUser->id : null,
                        'session_id' => 'demo-impression-'.$daysAgo.'-'.$placement->id.'-'.$index,
                        'source' => self::DEMO_PREFIX,
                        'channel' => 'advertising',
                        'dimensions' => ['placement_code' => $placement->code, 'campaign_id' => $campaign->id, 'demo' => true],
                        'occurred_at' => now()->subDays($daysAgo)->setTime(10, $index % 60),
                    ]);
                }
                foreach (range(1, $clicks) as $index) {
                    AnalyticsEvent::query()->create([
                        'event_uuid' => (string) Str::uuid(),
                        'event_name' => AnalyticsEventName::AdvertisingClicked,
                        'subject_type' => $creative->getMorphClass(),
                        'subject_id' => $creative->id,
                        'user_id' => $index % 2 === 0 ? $advertiserUser->id : null,
                        'session_id' => 'demo-click-'.$daysAgo.'-'.$placement->id.'-'.$index,
                        'source' => self::DEMO_PREFIX,
                        'channel' => 'advertising',
                        'dimensions' => ['placement_code' => $placement->code, 'campaign_id' => $campaign->id, 'demo' => true],
                        'occurred_at' => now()->subDays($daysAgo)->setTime(12, $index % 60),
                    ]);
                }
            }
        }

        AnalyticsEvent::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'event_name' => AnalyticsEventName::BlogPostViewed,
            'subject_type' => $blogPost->getMorphClass(),
            'subject_id' => $blogPost->id,
            'user_id' => $advertiserUser->id,
            'session_id' => 'demo-blog-view',
            'source' => self::DEMO_PREFIX,
            'channel' => 'blog',
            'dimensions' => ['demo' => true],
            'occurred_at' => now()->subHours(3),
        ]);
    }

    private function svg(int $width, int $height, string $placementName): string
    {
        $safePlacement = htmlspecialchars($placementName, ENT_QUOTES | ENT_XML1);
        $headlineSize = max(24, (int) round($height * 0.16));
        $bodySize = max(14, (int) round($height * 0.07));

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}" role="img" aria-labelledby="title desc">
<title id="title">SunHaven Naturist Resort demo advertisement</title>
<desc id="desc">A warm sunrise-inspired sponsored banner for the advertising pipeline demonstration.</desc>
<defs><linearGradient id="sky" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#0f4c5c"/><stop offset="0.55" stop-color="#e36414"/><stop offset="1" stop-color="#f6bd60"/></linearGradient></defs>
<rect width="{$width}" height="{$height}" rx="18" fill="url(#sky)"/>
<circle cx="{$width}" cy="0" r="{$height}" fill="#fff" opacity=".12"/>
<path d="M0 {$height} C {$width} {$height} {$width} {$height} {$width} {$height}" fill="#0b3d49" opacity=".45"/>
<text x="6%" y="42%" fill="#fff" font-family="Arial, sans-serif" font-size="{$headlineSize}" font-weight="700">Find Your Natural Escape</text>
<text x="6%" y="61%" fill="#fff" font-family="Arial, sans-serif" font-size="{$bodySize}">SunHaven Naturist Resort &bull; Sponsored Demo</text>
<rect x="72%" y="34%" width="22%" height="32%" rx="12" fill="#fff" opacity=".94"/>
<text x="83%" y="54%" text-anchor="middle" fill="#0f4c5c" font-family="Arial, sans-serif" font-size="{$bodySize}" font-weight="700">Explore SunHaven</text>
<text x="6%" y="88%" fill="#fff" opacity=".82" font-family="Arial, sans-serif" font-size="12">{$safePlacement} &bull; {$width}&times;{$height}</text>
</svg>
SVG;
    }

    private function stableUuid(string $key): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_DNS, 'uncladcollection.com/'.self::DEMO_PREFIX.'/'.$key)->toString();
    }
}
