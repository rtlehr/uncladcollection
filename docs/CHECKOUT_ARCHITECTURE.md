# Checkout Architecture

`CartEngine` owns cart mutation and aggregate repricing.

`CheckoutEngine` validates and reprices the cart, creates a pending order and immutable snapshots, prepares Stripe line items, and finalizes paid orders idempotently.

`StripeCheckoutService` is the payment-provider adapter. It does not calculate prices.

The signed webhook verifies the order identifier and Stripe total before calling `CheckoutEngine::markPaid()`.
