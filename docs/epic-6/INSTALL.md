# Epic 6.1 Installation

1. Back up the application and database.
2. Replace the project files with the package contents.
3. Run `composer install` if dependencies are not already installed.
4. Run `php artisan optimize:clear`.
5. Run `php artisan route:clear`.
6. Run `npm install` when needed, then `npm run build`.
7. Run `php artisan test --filter=PublicCustomerAccountTest`.
8. Log in as a customer and verify `/account`.
9. Log in as an administrator and verify `/admin` and the public `My Account` link.

There are no migrations in this package.
