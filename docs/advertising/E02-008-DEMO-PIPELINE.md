# E02-008 — Advertising Pipeline Demo Seeder

## Run the demo

```powershell
php artisan db:seed --class=AdvertisingPipelineDemoSeeder
```

The seeder is safe to run more than once. It updates the same stable demo records rather than creating duplicate advertisers, campaigns, proposals, invoices, or creatives.

## Demo login

```text
Email: advertiser@sunhaven-demo.test
Password: password
```

Use this account to inspect the advertiser portal, accepted proposal, campaign, creatives, invoice, payment history, and reports.

## Records created

- Advertiser: **SunHaven Naturist Resort (Demo)**
- Portal owner: **Jordan Ellis**
- Package: **Monthly Brand Partner (Demo)**
- Lead: completed/won opportunity with sales history
- Proposal: `DEMO-PROP-0001`
- Electronic acceptance and status audit trail
- Campaign: **SunHaven Summer Escape (Demo)**
- Four committed inventory reservations
- Four approved placement-ready SVG creatives
- Invoice: `DEMO-ADV-0001`
- Full manual payment and financial transaction
- Seven days of demo impressions and clicks
- Published instructional blog post

## Demo blog post

Open:

```text
/blog/how-sponsored-advertising-appears-on-unclad-collection-demo
```

The article demonstrates the correct implementation. The advertisement is **not embedded in the article body**. The public Blog Show page supplies this reusable placement after the article:

```vue
<PublicAdPlacement placement="blog-article-after-content" class="mt-12" />
```

This preserves editorial independence, campaign eligibility rules, tracking, scheduling, sponsored labeling, and graceful empty-placement behavior.

## Other public ad locations

The demo campaign also creates eligible creatives for:

```text
/
/images
/blog
```

The exact public locations are:

- `homepage-below-hero`
- `asset-gallery-inline`
- `blog-index-inline`
- `blog-article-after-content`

## Cleanup

```powershell
php artisan advertising:demo-cleanup
```

Noninteractive cleanup:

```powershell
php artisan advertising:demo-cleanup --force
```

The command removes the demo advertiser and its dependent records, the demo portal user, blog post, demo category/tag, demo analytics events, sponsorship package, and generated creative media directory.

## Test

```powershell
php artisan test --filter=AdvertisingPipelineDemoSeederTest
```
