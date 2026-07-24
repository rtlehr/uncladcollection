# UC-SUPPORT-001 — Ticket Architecture Foundation

This package adds the backend foundation for the Unclad Collection support-ticket system. It intentionally does not add routes, controllers, notifications, or Vue pages; those belong to UC-SUPPORT-002 and UC-SUPPORT-003.

## Included

- Ticket categories, tickets, messages, attachments, polymorphic relations, and status-history tables
- Typed status, priority, source, message-type, and attachment-scan enums
- Guest ticket token issuance, hashing, validation, and revocation
- Ticket number generation using the `UC-######` format
- Ticket creation, replies, internal notes, assignment, priority, transitions, and relation services
- Private attachment storage and validation foundation
- Record-level ticket policy
- Support permissions
- Default support categories
- Model factories and foundation tests

## Installation

1. Back up the database.
2. Copy all package files into the project root, preserving paths.
3. Run `composer dump-autoload`.
4. Run `php artisan migrate`.
5. Run `php artisan db:seed --class=PermissionSeeder`.
6. Run `php artisan db:seed --class=RoleSeeder` so administrators receive new permissions.
7. Run `php artisan db:seed --class=SupportTicketCategorySeeder`.
8. Run `php artisan optimize:clear`.
9. Run the focused tests listed in TESTING.md.

## Design notes

- Guest access tokens are returned once and stored only as SHA-256 hashes.
- Internal notes are separately typed and always stored with `is_customer_visible = false`.
- Related marketplace records use a polymorphic table so a ticket can reference multiple records.
- Attachments use private storage and begin with a `pending` scan status. A malware scanning job can be integrated later.
- Ticket deletion is not exposed. Soft deletion exists for controlled archival and future retention policies.
