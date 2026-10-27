# CodeBaseHub - Admin Website Management Console

CodeBaseHub is a secure, responsive administrative portal built on Laravel 13 and PHP 8.4 designed for managing web application deployments, template configurations, domain mappings, and operational metadata.

---

## Features

- **Secure Administrator Authentication**:
  - Guarded console routes (`/`, `/dashboard`, `/websites*`).
  - Session regeneration on login to prevent fixation attacks.
  - Complete session invalidation and CSRF token regeneration upon logout.
  - Brute-force rate limiting (5 attempts threshold) with localized retry cooldowns.
  - Masked password input via Artisan CLI (`php artisan make:admin`) without hardcoding credentials or enabling public registration.
- **Website Inventory & CRUD Operations**:
  - Full Eloquent lifecycle: Add, view details, edit, and delete websites.
  - Server-side validation with custom field-level error messages (`StoreWebsiteRequest`, `UpdateWebsiteRequest`).
  - Strict URL protocol validation (`url:http,https`) preventing protocol injection (e.g. `javascript:`, `ftp:`).
  - Explicit XSS prevention through safe Blade interpolation.
  - Two-step confirmation dialogs for destructive deletions.
- **Search, Filtering & Pagination**:
  - Server-side search across three primary fields simultaneously (`website_name`, `template_name`, `domain`).
  - Dropdown filtering by template architecture.
  - Database-efficient pagination (10 per page) with query string persistence (`withQueryString()`).
  - Contextual empty states and active filter indicators with one-click reset.
- **Dashboard & System Metrics**:
  - Real-time database-backed aggregates for total websites, distinct templates, and registered domains.
  - Recent websites overview with direct navigation to record details.
- **Responsive Layout**:
  - Built with Bootstrap 5.3, Bootstrap Icons, and Inter typography.
  - Mobile offcanvas sidebar with overlay and responsive tables.

---

## Prerequisites

Before setting up CodeBaseHub locally, ensure your environment meets the following requirements:

- **PHP**: `^8.3` or `^8.4` (tested on PHP 8.4.8)
  - Required PHP extensions: `pdo`, `pdo_mysql` (or `pdo_sqlite`), `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `curl`
- **Composer**: `^2.2` or later
- **Database Engine**:
  - **MySQL**: 8.0+ / MariaDB 10.4+ (for development/production)
  - **SQLite**: 3.35+ (supported out of the box and used for in-memory testing)
- **Node.js & NPM** (optional for asset compiling): Node `^18.0` / NPM `^9.0`

---

## Installation & Setup Guide

### 1. Clone & Install Dependencies

Clone the repository and install the Composer dependencies:

```bash
# Install PHP dependencies
composer install
```

### 2. Environment Configuration (`.env`)

Copy the example environment file and generate a secure application key:

```bash
# Duplicate environment template
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 3. MySQL Database Setup

1. Create a dedicated MySQL database for the application (e.g. via MySQL CLI or your administration tool):

```sql
CREATE DATABASE codebasehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Open `.env` and configure your database connection parameters:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=codebasehub
DB_USERNAME=your_mysql_username
DB_PASSWORD=your_mysql_password
```

*(Note: SQLite is also supported for rapid evaluation by setting `DB_CONNECTION=sqlite` and creating `database/database.sqlite`.)*

### 4. Run Migrations

Run database migrations to construct the `users`, `sessions`, `cache`, and `websites` tables:

```bash
php artisan migrate
```

### 5. Create Initial Administrator Account

CodeBaseHub restricts public registration. Create your administrator account safely via the interactive CLI command:

```bash
php artisan make:admin
```

You will be prompted for:
- **Administrator Name** (defaults to `Admin User`)
- **Administrator Email** (e.g. `admin@codebasehub.local`)
- **Password** (minimum 8 characters; input is securely masked in the console)

*Non-interactive flag execution is also supported:*
```bash
php artisan make:admin --name="Admin User" --email="admin@codebasehub.local" --password="YourSecurePassword123"
```

### 6. (Optional) Seed Sample Website Data

To populate your database with initial sample website records across diverse templates:

```bash
php artisan db:seed --class=WebsiteSeeder
```

---

## Running the Application

Start the Laravel local development server:

```bash
php artisan serve
```

The application will be accessible at:
- **Login Portal**: [http://127.0.0.1:8000/login](http://127.0.0.1:8000/login)
- **Dashboard**: [http://127.0.0.1:8000/dashboard](http://127.0.0.1:8000/dashboard)
- **Websites Directory**: [http://127.0.0.1:8000/websites](http://127.0.0.1:8000/websites)

---

## Automated Testing

CodeBaseHub includes a comprehensive PHPUnit feature test suite covering authentication, session handling, rate limiting, protected routes, CRUD lifecycle, server-side validation, search, pagination, and empty states.

Tests automatically execute in an isolated in-memory SQLite database (`:memory:`) without altering your local database:

```bash
# Run all tests
php artisan test

# Run tests with detailed test names
vendor/bin/phpunit --testdox

# Run specific feature test suites
php artisan test tests/Feature/Auth/AuthenticationTest.php
php artisan test tests/Feature/WebsiteCrudTest.php
php artisan test tests/Feature/WebsiteSearchAndPaginationTest.php
php artisan test tests/Feature/AdminDashboardTest.php
```

---

## Code Quality & Formatting

The codebase adheres to the Laravel Pint styling standard:

```bash
vendor/bin/pint --format agent
```

---

## Security Specifications

- **Protected Boundaries**: All operational endpoints (`/`, `/dashboard`, `/websites*`, `/logout`) enforce `auth` middleware. Guests attempting to access protected routes are redirected to `/login`.
- **Session Security**: Session IDs are regenerated on every successful login and invalidated on logout with CSRF token regeneration.
- **Password Security**: Passwords are never stored in plaintext and are hashed using bcrypt/argon2 via Laravel's `password => 'hashed'` cast.
- **Brute-Force Protection**: Rate limiting restricts excessive failed logins per IP/email pair to 5 attempts before enforcing a 60-second cooldown.
- **Input Sanitization**: HTML is automatically escaped via Blade templating. URL inputs reject non-HTTP(S) schemes.
