# E02-004 Advertising Billing

Advertising invoices are intentionally separate from marketplace customer orders. Invoice totals are derived from line items; payments and refunds update paid, refunded, and balance totals through `AdvertisingBillingService`.

Statuses: draft, issued, partially paid, paid, overdue, void, refunded.

Stripe Checkout uses advertising-specific metadata and the existing signed webhook endpoint. Webhook processing is idempotent through the advertising payment record and linked financial transaction.
