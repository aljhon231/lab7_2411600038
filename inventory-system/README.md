# Inventory Management System

A Laravel and MySQL inventory application for a construction-materials business. The project extends the existing Lab 5 and Lab 6 inventory features with supplier management, purchase orders, role-based purchasing approvals, stock replenishment, procurement metrics, notifications, and exports.

## Requirements

- PHP 8.4.1 or newer (the installed Laravel dependencies require it)
- Composer
- Node.js and npm
- XAMPP MariaDB/MySQL

The XAMPP PHP on this computer is 7.4.33, which cannot run this Laravel 13 project. A compatible PHP 8.5 CLI is available at `C:\php\php.exe`; use it for Artisan and the local web server. XAMPP MariaDB can continue to provide MySQL.

## Setup on Windows

Run these commands from `C:\xampp\htdocs\lab7_2411600038\inventory-system`:

```powershell
& 'C:\composer\composer.bat' install
if (!(Test-Path .env)) { Copy-Item .env.example .env }
```

If this is a fresh setup and `APP_KEY` is empty, generate it with `& 'C:\php\php.exe' artisan key:generate`. Keep an existing `.env` and application key; do not overwrite them.

Create the database in XAMPP phpMyAdmin or the MySQL command line:

```sql
CREATE DATABASE inventory_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Set these values in `.env` (leave the password empty for a default local XAMPP root account):

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_system
DB_USERNAME=root
DB_PASSWORD=
```

Then apply additive migrations and (only when desired) seed demo data:

```powershell
& 'C:\php\php.exe' artisan migrate
& 'C:\php\php.exe' artisan db:seed
npm.cmd install
npm.cmd run build
```

Start the local development server:

```powershell
& 'C:\php\php.exe' artisan serve --host=127.0.0.1 --port=18000
```

Open `http://127.0.0.1:18000`. The usual port 8000 is already used by another local project on this computer.

The database seeder creates this local demonstration account:

- Email: `admin@example.com`
- Password: `admin123`

Change the demonstration password before using the application outside a local lab environment.

## Main features

- Breeze login, registration, logout, and authenticated application routes
- Product create, view, edit, search, validation, and stock status badges; products with history cannot be deleted
- Stock-in, stock-out, and signed adjustment records tied to the signed-in user
- An inventory transaction observer updates stock atomically and prevents negative inventory
- Date/product/type transaction filters, archived transaction reversals for administrators, and product running-balance history
- Low-stock notifications to administrators, unread notification menu, and optional SMTP email delivery
- Dashboard statistics, low-stock and recent-transaction APIs, and 30-second AJAX polling
- Stock adjustments, movement/low-stock/valuation reports, and CSV export
- Supplier CRUD, search, filters, soft delete/restore, delivery terms, and linked products
- Purchase orders with item-level price snapshots, filtering, approvals, rejection, supplier sending, and partial receipts
- Purchase receipt inventory transactions that use the existing observer for a single stock update
- Role-based purchase-order permissions (`admin`, `manager`, `warehouse_staff`, and regular users)
- Reorder point calculations from stock-out transactions over the previous 90 days; `reorder_level` is used as safety stock
- Daily scheduled `inventory:check-reorder` command that generates duplicate-safe draft purchase orders
- Procurement dashboard widgets and authenticated JSON endpoints with 30-second polling
- Supplier performance metrics, on-time delivery trend, and cached report data
- CSV and PDF exports for products, purchase orders, inventory transactions, and supplier performance
- Queued purchase-order emails and the existing low-stock notification system; email sending is skipped unless SMTP is configured
- Dashboard counts and inventory value calculated from the database
- Fifteen repeatable sample hardware products
- Responsive Bootstrap screens using the Lab 4 green palette

## Useful commands

```powershell
& 'C:\php\php.exe' artisan route:list
& 'C:\php\php.exe' artisan migrate:status
& 'C:\php\php.exe' artisan inventory:check-reorder
& 'C:\php\php.exe' artisan schedule:list
& 'C:\php\php.exe' artisan test
npm.cmd run build
```

`artisan migrate` applies additive database changes without resetting inventory. `artisan db:seed` adds the demo account and only creates opening-stock transactions for new sample products. `artisan test` runs the automated feature tests.

The queue connection is database-backed. Start a worker in another terminal to process low-stock database notifications and any configured mail delivery:

```powershell
& 'C:\php\php.exe' artisan queue:work --tries=3
```

Inspect failed jobs with `artisan queue:failed`; retry them with `artisan queue:retry all`. Low-stock notifications are queued only when inventory first crosses to the reorder threshold, and the low-stock state resets after inventory returns above it.

The scheduler is defined in `routes/console.php`. For local verification, run `& 'C:\php\php.exe' artisan schedule:work` in a separate terminal; the reorder check runs daily. The command can also be run manually with `artisan inventory:check-reorder`.

Purchase roles are stored on `users.role`. Existing administrators remain administrators through `is_admin`; the migration also sets their role to `admin`. Assign `manager` or `warehouse_staff` only to accounts that need those permissions.

To send purchase-order email, configure `MAIL_MAILER=smtp`, the SMTP host/port/username/password, and `MAIL_FROM_ADDRESS` in the local `.env`, then run a queue worker. The default local mail configuration is not treated as successful delivery.

The additional supplier, purchase-order, and role migrations are additive. Run `artisan migrate`; never use `migrate:fresh` on the existing inventory database.

## Screenshots

Capture the requested screenshots locally after running the application. This project does not include fabricated screenshots.

## Laboratory answers

See [LABORATORY-ANSWERS.md](./LABORATORY-ANSWERS.md) for student-friendly explanations of MVC, Eloquent, validation, and the Lab 4 color palette.
