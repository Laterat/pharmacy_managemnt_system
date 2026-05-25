# Pharmacy Inventory Management System

Built with plain HTML, CSS, JavaScript, PHP, and MySQL.

## Setup

1. Open MySQL Workbench, phpMyAdmin, or the MySQL terminal.
2. Import `database.sql`.
3. If your MySQL password is not empty, edit `includes/db.php`:

```php
$dbUser = 'root';
$dbPass = 'your_password_here';
```

4. Start a PHP server from the project folder:

```powershell
php -S localhost:8000
```

5. Open:

```text
http://localhost:8000/auth/login.php
```

## Demo Login

- Admin: `admin` / `admin123`
- Staff: `staff` / `admin123`

## Main Features

- Login/logout with PHP sessions
- Password hashing
- Role-based delete access for admins
- Medicine stock entry, editing, listing, filtering, and deleting
- Expired and expiring-soon alerts
- Low-stock notifications
- Sales/dispensing with automatic stock reduction
- Receipt display and printing
- Category management
- Date-filtered sales reports
- Low-stock and expiry reports
