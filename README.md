# My Warehouse

A responsive warehouse management application with a Greek user interface, built with plain PHP, PDO, SQLite, HTML, CSS and JavaScript. It was created as a personal portfolio project without a framework or Composer.

## Features

- Dashboard with key statistics, inventory value and low-stock alerts.
- Product management with unique SKUs, prices, suppliers and minimum stock levels.
- Stock receipts and withdrawals with a complete transaction history.
- Customer and supplier management.
- Multi-item orders with automatic total calculation.
- Automatic stock deduction inside a database transaction.
- Full order rollback when available stock is insufficient.
- Responsive layout for desktop and mobile devices.

## Technologies

- PHP 8.1+
- PDO and SQLite
- HTML5, CSS3 and vanilla JavaScript
- GitHub Actions for syntax checks and functional tests

## Run Locally

### Windows — one-click start

1. Download and extract the project.
2. Double-click `START-PHP.bat`.
3. Keep the command window open while using the application.
4. The application will open at `http://127.0.0.1:8000`.

The launcher uses PHP from XAMPP or your PATH when available. If PHP is not installed, it downloads the official portable PHP runtime on the first run and stores it inside the ignored `runtime` directory. An internet connection is required only for this initial setup.

### Using a terminal

```bash
php -S 127.0.0.1:8000 -t public
```

The `data/warehouse.sqlite` database and the sample data are created automatically on the first run.

## Run the Tests

```bash
php tests/run.php
```

The tests verify sample-data creation, stock deduction, order total calculation, rollback when stock is insufficient and reconciliation between product balances and inventory transactions.

## Project Structure

```text
app/
  bootstrap.php       SQLite connection and initialization
  functions.php       validation, CSRF and HTML helpers
  services.php        stock and order business logic
  schema.sql          tables, relationships and constraints
public/
  assets/             CSS and JavaScript
  *.php               application pages
data/                  local database, created automatically
tests/run.php          functional tests
START-PHP.bat          Windows launcher
setup-php.ps1          automatic portable PHP setup
```

See the [database diagram](docs/database-schema.md) for the main entities and relationships.

## Security and Data Integrity

The application uses prepared SQL statements, CSRF tokens, server-side validation, HTML escaping, security headers and database transactions. This repository is a local portfolio demo. Public production use would also require authentication, authorization, HTTPS and production web-server configuration.

## CV Description

**Greek Warehouse Management System — Personal Project | PHP, SQLite, PDO, HTML, CSS, JavaScript**

- Developed a responsive warehouse management application with a Greek user interface.
- Designed a relational SQLite database for products, suppliers, customers, orders and inventory transactions.
- Implemented transactional stock updates, low-stock alerts, order totals and inventory history using PHP PDO.
- Applied prepared SQL statements, CSRF protection, server-side validation and output escaping.

All names and contact details in the sample data are fictional.

## License

This project is available under the [MIT License](LICENSE).
