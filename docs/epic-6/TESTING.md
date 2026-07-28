# Epic 6.1 Testing

## Automated

- Public account dashboard renders for verified customers.
- Ordinary customers receive 403 at `/dashboard`.
- Staff users with `view_admin` are redirected from `/dashboard` to `/admin`.
- Old `/purchases` links redirect to `/account/library`.
- Customer login redirects to `/account`.

## Manual

- Verify desktop and mobile public header account links.
- Verify Administration is hidden from ordinary customers.
- Verify Administration appears for staff.
- Verify My Library and license detail pages retain public branding.
- Verify Profile, Security, and Appearance use public account navigation.
- Verify recently viewed and recommendation cards link to public assets.
- Verify empty states with a new customer account.
