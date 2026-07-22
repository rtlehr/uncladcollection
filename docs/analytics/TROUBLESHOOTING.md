# Analytics Troubleshooting

## New Vue page missing from Vite manifest

Run:

```bash
php artisan optimize:clear
npm run build
```

Confirm the page exists under the exact case-sensitive `resources/js/pages/Admin/Analytics` path.

## Analytics route returns 403

Confirm the user is verified and has both `view_admin` and `view_reports`.

## Report appears stale

Wait for the configured report-cache period or clear application cache:

```bash
php artisan cache:clear
```

New analytics events normally invalidate the analytics report cache version automatically.

## Duplicate events

Review `ANALYTICS_DEDUPLICATE` and `ANALYTICS_DEDUPLICATION_WINDOW_SECONDS`. The fingerprint intentionally suppresses equivalent events inside the configured short window.

## Missing events

Check:

- `ANALYTICS_ENABLED`
- Bot filtering and the request user agent
- Required subject/user/session context
- Application logs
- Analytics metadata limits

## Memory exhaustion

Look for immutable-date loops that do not assign the returned date, unbounded collection loading, or PHP-side grouping over large datasets. Timeline loops using `CarbonImmutable` must use `$date = $date->addDay()`.

## MariaDB SQL syntax errors

Avoid reserved words as raw aliases, including aliases such as `lines`. Prefer descriptive aliases such as `cart_line_count`.

## Validation command fails

Run:

```bash
php artisan analytics:validate --strict
```

Correct each missing route, permission, table, column, or configuration item reported by the command before deploying.
