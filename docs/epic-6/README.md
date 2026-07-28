# Epic 6.1 — Public Customer Account Foundation

This package separates the authenticated customer experience from the internal administration application.

## Included

- Public `/account` dashboard using the normal site header and footer.
- Account navigation for library, favorites, profile, security, and appearance.
- Personalized dashboard summaries, recent licenses, recently viewed assets, and recommendations.
- My Library and license detail pages moved into the public account layout.
- Customer settings moved into the public account layout.
- Permission-aware login destinations.
- `/dashboard` restricted to users with `view_admin` and redirected to `/admin`.
- Backward-compatible `/purchases` and license-detail redirects.
- Public header `My Account` link and staff-only `Administration` link.
- Internal sidebar reduced to administrative destinations.

No database migration is required for Package 6.1.
