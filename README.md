# Pharmacy Management System

A pure PHP/MySQL pharmacy management system with role-based access, inventory, sales, suppliers, users, and reporting. It uses no frameworks or frontend libraries.

## Features

- Secure login with `password_hash()` and `password_verify()`
- Admin and pharmacist roles
- Inventory CRUD with low-stock and expiry alerts
- POS checkout with FEFO batch stock deduction
- Sales reports, top medicines, expired stock, and CSV export
- Supplier CRUD
- User management with enable/disable and delete protection
- CSRF tokens, prepared statements, server-side validation, and escaped output

## Installation

1. Copy the project into your PHP server directory, for example `htdocs/pharmacy_system`.
2. Create a MySQL database by importing `database.sql`.
3. Update database credentials in `config/db.php`.
4. Open the project in a browser:

```text
http://localhost/pharmacy_system/
```

## Default Login

```text
Username: admin
Password: admin123
```

Change the default password after first login.

## Folder Structure

- `assets/css/style.css`: application styling
- `assets/js/validation.js`: client-side validation and delete confirmations
- `config/db.php`: MySQL connection
- `includes/`: shared auth, helpers, header, and footer
- `modules/`: inventory, sales, user auth, and supplier logic
- Top-level PHP files: page controllers and views
- `database.sql`: schema and starter data

## Notes

The `.gitignore` excludes `config/db.php` because it can contain local credentials. Keep a safe local copy of your database settings.
