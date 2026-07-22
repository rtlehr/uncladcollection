# Marketplace Intelligence Metric Definitions

## Revenue

- **Revenue:** paid order value in the selected reporting period unless a report explicitly labels the value as collected, net, influenced, or lifetime revenue.
- **Collected revenue:** completed financial collections recorded by the financial ledger.
- **Net revenue:** collected revenue less recorded refunds.
- **Influenced revenue:** paid revenue from registered users who interacted with the measured campaign, blog post, or search experience during the selected period. It is an influence signal, not proof of causation.

## Customers and conversion

- **Buyer:** a user with at least one paid order in the period.
- **New customer:** a buyer whose first paid order occurred in the period.
- **Repeat customer:** a buyer with a paid order before the period and another paid order in the period.
- **Conversion rate:** the later-stage count divided by the applicable earlier-stage count. A zero denominator returns zero.
- **Abandoned cart:** an active cart line unchanged for at least 24 hours. It is a re-engagement indicator, not confirmation that checkout was intentionally abandoned.

## Assets and content

- **Asset view:** a tracked `asset_viewed` event.
- **Blog view:** a tracked `blog_post_viewed` event.
- **Unique viewer/reader:** a distinct authenticated user or, when available, anonymous session.
- **Engagement rate:** report-specific engaged actions divided by views or readers, as labeled on the page.

## Campaigns and search

- **Campaign impression:** a campaign hero was rendered and tracked.
- **Campaign click-through rate:** tracked campaign button clicks divided by campaign impressions.
- **Search:** a gallery request containing a search term, filter, suggestion selection, or non-default sort that generated a search analytics event.
- **Zero-result search:** a tracked search whose stored result count is zero.
- **Low-result search:** a tracked search whose result count falls below the report threshold.

## Downloads and licenses

- **License utilization:** downloads used divided by the license download limit when a finite limit exists.
- **Unused license:** a purchased license with no recorded downloads.
- **Near-limit license:** a license whose downloads used are close to its configured limit, using the threshold shown by the report.

## Operations

- **Payment success rate:** paid orders divided by paid plus failed orders in the period.
- **Stalled paid order:** a paid order not delivered, fulfilled, or canceled after 24 hours.
- **Manual attention:** failed, refunded, partially refunded, or stalled paid orders.
- **Average fulfillment time:** average elapsed time from payment to fulfillment for orders with both timestamps.
