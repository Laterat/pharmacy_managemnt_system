# ASD Pharmacy Management System

A plain PHP and MySQL pharmacy management system for small pharmacy operations. It covers secure sign-in, role-based access, medicine and category management, supplier records, point-of-sale checkout, batch-aware stock deduction, sales reporting, expired-stock visibility, and basic account administration.

The project intentionally uses simple server-rendered PHP pages, MySQLi prepared statements, vanilla JavaScript, and one shared stylesheet. There is no framework, package manager, build step, or frontend dependency chain.

## Table of Contents

- [What This App Does](#what-this-app-does)
- [Feature Overview](#feature-overview)
- [Roles and Permissions](#roles-and-permissions)
- [Tech Stack](#tech-stack)
- [Repository Map](#repository-map)
- [Database Model](#database-model)
- [Installation](#installation)
- [First Run Checklist](#first-run-checklist)
- [Default Login](#default-login)
- [Main Workflows](#main-workflows)
- [Security Notes](#security-notes)
- [Known Current-State Notes](#known-current-state-notes)
- [Development Notes](#development-notes)
- [Troubleshooting](#troubleshooting)

## What This App Does

ASD Pharmacy Management System is a browser-based back-office and sales tool. Admin users maintain the catalog, stock data, suppliers, categories, reports, and staff accounts. Pharmacist users operate the POS screen and sell medicines from available stock.

The application tracks medicine stock at two levels:

- `medicines.stock_quantity` stores the current total stock shown across inventory and POS screens.
- `batches.quantity` stores stock per batch with an expiry date and batch number.

During checkout, sales are wrapped in a database transaction. Stock is deducted from the earliest valid expiry batches first, which gives the system a FEFO-style flow: first-expiring, first-out.

## Feature Overview

- Login with hashed passwords using PHP `password_hash()` and `password_verify()`.
- Session-based authentication.
- CSRF token generation and validation for forms.
- Admin and pharmacist roles.
- Dashboard with total medicines, low stock, expiring stock, and today's sales.
- Inventory search and category filtering.
- Admin medicine creation, editing, deletion, and category management.
- Supplier create, update, list, and protected delete.
- POS cart with client-side quantity controls.
- Transactional sales processing with stock checks.
- Batch deduction ordered by expiry date.
- Daily, weekly, monthly, yearly, and top-selling reports.
- Expired stock table on the reports page.
- User creation, enable/disable, and protected delete.
- Profile page for updating username, full name, and password.
- Shared HTML escaping helper for safe output.
- Vanilla JS validation, cart rendering, form toggles, and delete confirmation modals.

## Roles and Permissions

### Admin

Admins can:

- View dashboard metrics.
- View, search, add, edit, and delete medicines.
- Add and delete categories.
- Manage suppliers.
- View sales reports and expired stock.
- Create pharmacist users.
- Enable, disable, and delete eligible users.
- Update their own profile.

### Pharmacist

Pharmacists can:

- View dashboard metrics.
- View and search inventory.
- Use the POS checkout flow.
- Update their own profile.

The navigation in `assets/includes/header.php` shows POS only to pharmacists and admin-only pages only to admins.

## Tech Stack

- PHP with MySQLi.
- MySQL or MariaDB.
- HTML, CSS, and vanilla JavaScript.
- Server-rendered pages.
- No Composer dependencies.
- No Node dependencies.
- No frontend framework.

## Repository Map

```text
.
|-- add_medicine.php
|-- dashboard.php
|-- database.sql
|-- edit_medicine.php
|-- index.php
|-- inventory.php
|-- logout.php
|-- pos.php
|-- profile.php
|-- reports.php
|-- suppliers.php
|-- users.php
|-- config/
|   `-- db.php
|-- modules/
|   |-- inventory_logic.php
|   |-- sales_process.php
|   |-- supplier_logic.php
|   `-- user_auth.php
`-- assets/
    |-- css/
    |   `-- style.css
    |-- img/
    |   |-- favicon.ico
    |   `-- logo.png
    |-- includes/
    |   |-- auth.php
    |   |-- footer.php
    |   |-- functions.php
    |   `-- header.php
    `-- js/
        `-- validation.js
```

### Top-Level Pages

- `index.php`: Login page. Redirects logged-in users to `dashboard.php`.
- `dashboard.php`: Authenticated dashboard with inventory, low-stock, expiry, and sales views.
- `inventory.php`: Medicine list, search, category filter, admin delete action, and admin category manager.
- `add_medicine.php`: Admin-only medicine creation form.
- `edit_medicine.php`: Admin-only medicine update form.
- `pos.php`: Pharmacist-only point-of-sale page.
- `reports.php`: Admin-only reports page with sales summaries and expired stock.
- `suppliers.php`: Admin-only supplier management.
- `users.php`: Admin-only user management.
- `profile.php`: Authenticated account update page.
- `logout.php`: Destroys the active session and returns to login.

### Modules

- `modules/inventory_logic.php`: Medicine CRUD, medicine validation, and medicine fetch helpers.
- `modules/sales_process.php`: Transactional sale creation, cart POST handling, batch deduction, and stock updates.
- `modules/supplier_logic.php`: Supplier CRUD and validation.
- `modules/user_auth.php`: User listing, creation, status changes, and protected deletion.

### Shared Includes

- `assets/includes/auth.php`: Session start, login checks, role checks, and access guards.
- `assets/includes/functions.php`: HTML escaping, price formatting, stock counters, CSRF helpers, category and supplier fetch helpers.
- `assets/includes/header.php`: Main layout opening, navigation, and low-stock badge.
- `assets/includes/footer.php`: Main layout closing, footer, and JavaScript include.

### Assets

- `assets/css/style.css`: Global layout, forms, dashboard cards, tables, modal, login page, and responsive rules.
- `assets/js/validation.js`: Negative number validation, POS cart behavior, user/supplier form toggles, and delete modal behavior.
- `assets/img/logo.png`: Login page background image.
- `assets/img/favicon.ico`: Browser favicon.

## Database Model

`database.sql` creates the `pharmacy_system` database and these tables:

| Table | Purpose |
| --- | --- |
| `users` | Staff accounts, roles, active status, password hash, and full name. |
| `categories` | Medicine categories such as analgesics or antibiotics. |
| `suppliers` | Supplier contact records. |
| `medicines` | Main medicine catalog and total stock. |
| `batches` | Per-medicine batch quantities and expiry dates. |
| `sales` | Sale header records with user and total amount. |
| `sale_items` | Line items for each sale. |
| `audit_logs` | Audit table schema exists, but the current PHP pages do not write to it yet. |

Key relationships:

- `medicines.category_id` references `categories.id`.
- `medicines.supplier_id` references `suppliers.id`.
- `batches.medicine_id` references `medicines.id` with cascade delete.
- `sales.user_id` references `users.id`.
- `sale_items.sale_id` references `sales.id` with cascade delete.
- `sale_items.medicine_id` references `medicines.id`.
- `audit_logs.user_id` references `users.id` with `ON DELETE SET NULL`.

Seed data:

- Categories: `Analgesics`, `Antibiotics`, `Antiseptics`.
- Supplier: `Default Supplier`.
- Admin user: `admin`.

## Installation

### Requirements

- PHP 8.x recommended.
- MySQL 5.7+ or MariaDB 10.x+.
- A local PHP stack such as XAMPP, WAMP, Laragon, MAMP, or PHP's built-in server plus MySQL.
- PHP MySQLi extension enabled.

### 1. Put the Project in a Web Root

Example with XAMPP on Windows:

```text
C:\xampp\htdocs\Pharmacy_managemnt_system
```

Then open:

```text
http://localhost/Pharmacy_managemnt_system/
```

### 2. Import the Database

Using phpMyAdmin:

1. Open phpMyAdmin.
2. Import `database.sql`.
3. Confirm that the `pharmacy_system` database exists.

Using MySQL CLI:

```bash
mysql -u root -p < database.sql
```

### 3. Configure the Database Connection

Edit `config/db.php`:

```php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'your_password';
$db_name = 'pharmacy_system';
```

Do not commit real production credentials. The repository currently contains a local `config/db.php`, while `.gitignore` also lists `/config/db.php`. Treat that file as environment-specific.

### 4. Confirm Include Paths

The PHP pages currently require shared files from:

```php
__DIR__ . '/includes/auth.php'
__DIR__ . '/includes/functions.php'
__DIR__ . '/includes/header.php'
__DIR__ . '/includes/footer.php'
```

In this repository, the actual shared files are located in:

```text
assets/includes/
```

Before running the app as-is, either:

- move or copy `assets/includes/` to a top-level `includes/` directory, or
- update the `require_once` paths in the PHP files to point at `assets/includes/`.

Without this, PHP will fail on missing include files.

## First Run Checklist

- Import `database.sql`.
- Update `config/db.php`.
- Resolve the `includes/` versus `assets/includes/` path mismatch.
- Confirm PHP MySQLi is enabled.
- Open the login page.
- Sign in with the default admin account.
- Change the default admin password immediately.
- Add real suppliers, categories, medicines, stock quantities, and expiry dates.
- Create pharmacist users from the Users page.

## Default Login

```text
Username: admin
Password: admin123
```

Change this password immediately after first login.

## Main Workflows

### Login

1. `index.php` displays the login form.
2. The form submits username, password, and CSRF token.
3. PHP fetches the user by username.
4. Login succeeds only if the account is active and `password_verify()` passes.
5. Session values are set for user ID, username, full name, and role.
6. The user is redirected to `dashboard.php`.

### Dashboard

`dashboard.php` calculates:

- total medicines,
- total stock units,
- low-stock count,
- expiring-within-30-days count,
- today's sales total.

It also supports detail views through `?view=`:

- `medicines`
- `low_stock`
- `expiring`
- `sales`

### Inventory Management

Admins can:

- add medicines,
- edit medicines,
- delete medicines,
- add categories,
- delete categories when not blocked by related medicines.

All authenticated users can search inventory by medicine or generic name and filter by category.

Medicine validation requires:

- name,
- generic name,
- SKU,
- category,
- supplier,
- price greater than or equal to 0,
- stock quantity greater than or equal to 0,
- minimum stock level greater than or equal to 0,
- expiry date.

### POS Checkout

`pos.php` lets pharmacists search medicines with available stock and add them to a cart. The browser builds a JSON cart and posts it to `modules/sales_process.php`.

`create_sale()` then:

1. starts a transaction,
2. calculates sale total,
3. inserts a `sales` row,
4. checks total medicine stock,
5. selects unexpired batches ordered by earliest expiry,
6. deducts batch quantities,
7. inserts `sale_items`,
8. deducts `medicines.stock_quantity`,
9. commits the transaction.

If stock is insufficient or no unexpired batch can satisfy the sale, the transaction is rolled back.

### Reports

Admins can view:

- daily sales,
- weekly sales,
- monthly sales,
- yearly sales,
- top-selling medicines,
- expired stock.

Date-filtered reports default to:

- `from`: first day of the current month,
- `to`: current date.

### Supplier Management

Admins can create, update, and delete supplier records. Supplier deletion is blocked if any medicine references that supplier.

### User Management

Admins can:

- create pharmacist accounts,
- enable users,
- disable users,
- delete users who do not have sales records.

Admins cannot modify their own account from the Users page. Own-account updates happen on `profile.php`.

### Profile Management

Authenticated users can update:

- full name,
- username,
- password.

Current password is required for profile changes.

## Security Notes

Implemented protections:

- Password hashes are stored instead of plain-text passwords.
- Login uses `password_verify()`.
- Sessions are regenerated on successful login.
- Forms use CSRF tokens.
- SQL queries use prepared statements.
- Output helper `h()` escapes HTML.
- Role guards protect admin and pharmacist-only pages.
- User deletion is blocked when sales records exist.
- Supplier deletion is blocked when medicine records exist.
- Sales processing uses a transaction to avoid partial stock updates.

Recommended hardening before production:

- Move database credentials out of version control.
- Rotate any credentials that were ever committed.
- Serve the app over HTTPS.
- Set secure session cookie options.
- Add server-side logging for `audit_logs`.
- Add rate limiting or lockout for repeated failed login attempts.
- Add stronger password policy for created users.
- Review all JavaScript DOM injection paths if medicine names can contain user-controlled HTML.
- Add authorization tests for every protected route.

## Known Current-State Notes

These are based on the repository as currently checked in:

- The app requires top-level `includes/...`, but shared include files are stored under `assets/includes/...`.
- `.gitignore` lists `/config/db.php`, but `config/db.php` is present in the repository and contains local credentials.
- `audit_logs` exists in the schema, but no current module writes audit events.
- `reports.php` contains a mojibake back-arrow string in the Back button text.
- `users.php` accepts a `role` input in the form, but the server currently forces new users to `pharmacist`.
- `assets/js/validation.js` assumes some page-specific elements exist. Some handlers use optional checks, but the user-form toggle accesses elements directly and should be guarded if the script is loaded on every page.
- There is no automated test suite yet.

## Development Notes

### Code Style

- PHP is procedural.
- Page files act as controllers and views.
- Reusable business logic lives in `modules/`.
- Shared session, authorization, formatting, and CSRF helpers live in the includes.
- Database access uses MySQLi prepared statements.

### Adding a New Admin Page

1. Require the database, auth helpers, and shared functions.
2. Call `requireAdmin()`.
3. Handle POST actions after CSRF validation.
4. Use prepared statements for queries.
5. Escape output with `h()`.
6. Include the shared header and footer.

Skeleton:

```php
<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        // Handle invalid token.
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <h1>Page Title</h1>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
```

### Adding a New Report

1. Add a new `case` in the `reports.php` report switch.
2. Keep `from` and `to` filtering consistent.
3. Render the result table in the report detail block.
4. Add a link card to the reports landing state.

### Stock Consistency Rule

When changing stock manually, keep this invariant in mind:

```text
medicines.stock_quantity should equal the sum of non-deleted batch quantities for that medicine.
```

The current edit form updates the earliest batch quantity when an expiry date is supplied. If you add multi-batch receiving later, create a dedicated stock receiving workflow instead of overloading medicine edits.

## Troubleshooting

### Blank Page or Missing File Error

Check PHP error logs. If the error mentions `includes/auth.php`, resolve the include path mismatch described in [Confirm Include Paths](#4-confirm-include-paths).

### Database Connection Failed

Check:

- MySQL service is running.
- `config/db.php` has the correct host, username, password, and database name.
- `pharmacy_system` was imported.
- PHP MySQLi is enabled.

### Login Fails With Default Account

Check:

- `database.sql` imported successfully.
- The `users` table contains the `admin` user.
- The account has `is_active = 1`.
- You are using `admin123` unless the password was changed.

### POS Sale Fails

Possible causes:

- Cart is empty.
- Medicine total stock is too low.
- Available batches are expired.
- Batch quantities do not match the medicine total stock.
- Database transaction failed.

### Cannot Delete Supplier

The supplier is probably referenced by one or more medicines. Reassign or delete those medicines first.

### Cannot Delete User

Users with sales records are protected from deletion. Disable the account instead.

## Suggested Next Improvements

- Fix include paths or move includes to the expected top-level directory.
- Remove committed database credentials and use a local config template.
- Add audit logging for login, inventory, supplier, user, and sales actions.
- Add automated smoke tests for login guards and core sales logic.
- Add a receiving workflow for adding new medicine batches.
- Add CSV export if report export is needed.
- Add pagination for inventory, users, suppliers, and sales reports.
- Add printable receipts for POS sales.
- Add stronger JavaScript null guards for page-specific scripts.
- Add database indexes for search and reporting columns.

## License

No license file is currently included. Add a license before publishing or distributing the project.
