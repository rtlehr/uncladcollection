# UC-SUPPORT-002 — Public and Member Support Center

Adds public guest submission, secure token-based guest tracking, authenticated member ticket management, replies, private attachments, email confirmations, public/member navigation, and focused tests.

## Install
1. Copy all package files over the project root.
2. Run `composer dump-autoload`.
3. Run `php artisan optimize:clear`.
4. Run `npm run build`.
5. Run `php artisan test tests/Feature/Support/SupportCenterTest.php`.

No new migration is required. UC-SUPPORT-001 must already be installed.
