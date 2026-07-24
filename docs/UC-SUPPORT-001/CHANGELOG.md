# Changelog

## UC-SUPPORT-001

### Added

- Six support-ticket database tables
- Five support enums
- Six support models and user relationships
- `SupportTicketService`
- `SupportTicketNumberService`
- `GuestTicketAccessService`
- `SupportTicketAttachmentService`
- `SupportTicketPolicy`
- Eight support permissions
- Nine default ticket categories
- Ticket factories and foundation feature tests
- `config/support.php`

### Changed

- `User` now exposes owned and assigned support-ticket relationships.
- `PermissionSeeder` includes the Support permission group.
- `DatabaseSeeder` includes default support categories.
