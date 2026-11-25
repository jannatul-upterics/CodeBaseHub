# CodeBaseHub

CodeBaseHub is a Laravel-based admin portal for managing multiple websites from one place. It handles website inventory, domains, pages, sections, themes, templates, media, settings, and SEO metadata. Each managed website can connect to its own separate tenant database while being administered through the central dashboard.

---

## Main Features

- **Admin Authentication**: Protected dashboard and resource routes with login rate limiting (5 attempts), session regeneration on login/logout, and no public registration. Admin accounts are created via Artisan (`php artisan make:admin`).
- **Website Management & Workspace**: Create, edit, search, filter, and delete websites. Each website gets a dedicated workspace (`/websites/{website}/...`) for managing:
  - **Pages & Sections**: Create, edit, soft-delete (trash/restore), and reorder page sections.
  - **Themes & Templates**: Assign templates and themes, and customize per-website theme settings.
  - **Domains**: Manage primary and secondary domains and track DNS/SSL verification status.
  - **Media, Settings & SEO**: Upload and manage media files, key-value site settings, and page/website metadata.
- **Customer Management**: Track customers and associate them with websites.
- **Multi-Tenant Database Support**: Store and test per-website database credentials, connect to existing tenant databases, and sync content between the central database and each website's tenant database.
- **Public Page API**: Expose published page data to external frontends via `/api/v1/pages`. Websites are identified using the `X-Website-Domain` header or `?domain=` query parameter — no special configuration needed.
- **Bidirectional Page Sync**: Pages, sections, and SEO metadata can be synchronized between the central database and each website's tenant database using `PageSyncService` and the `app:sync-central-pages` Artisan command.

---

## Tech Stack

- **PHP**: `^8.3` (tested on PHP 8.4)
- **Framework**: Laravel `^13.17`
- **Multi-Tenancy**: `stancl/tenancy` `3.10`
- **Frontend**: Blade templates, Bootstrap 5.3, Bootstrap Icons, Instrument Sans font, and custom CSS (`public/css/cbh-design-system.css`), with Vite (`^8.0`) and Tailwind CSS (`^4.0`) configured
- **Database**: SQLite (default in `.env.example` and testing) or MySQL / MariaDB
- **Testing & Formatting**: PHPUnit `^12.5` and Laravel Pint `^1.27`

---

## How Tenancy & Databases Work

CodeBaseHub separates data into a **central database** and per-website **tenant databases**.

### Central Database

Stores all shared platform data:
- `users`, `sessions`, `cache`, `jobs`
- `customers`, `themes`, `templates`
- `websites` (`App\Models\Website` acts as the Stancl/Tenancy `Tenant` model)
- `domains` (`App\Models\Domain` acts as the Stancl/Tenancy `Domain` model, linked via `tenant_id`)
- `website_database_connections` (stores `driver`, `host`, `port`, `database`, `username`, and an encrypted `password` for each website)
- `website_theme_settings`
- Central copies of `pages`, `page_sections`, `media`, `settings`, and `metadata`

### Tenant Databases

Each website uses its own tenant database containing:
- `pages`, `media`, `page_sections`, `settings`, `metadata`

Migrations for these tables live in `database/tenant_migrations/`.

### How It Works

- `config/tenancy.php` sets `Website::class` as the `tenant_model` and `Domain::class` as the `domain_model`.
- Automatic database creation/deletion listeners are intentionally left empty. Database connections are managed explicitly through the `website_database_connections` table.
- `App\Tenancy\Bootstrappers\WebsiteDatabaseTenancyBootstrapper` reads a website's `WebsiteDatabaseConnection` record, builds the `tenant` Laravel connection at runtime, and switches the default database connection to `tenant` (reverting on tenancy end).
- `App\Http\Middleware\TenantConnection` (registered as the `tenant` alias in `bootstrap/app.php`) resolves the target website from the `X-Tenant-Domain`, `X-Domain`, or `Host` header (or a route `{website}` parameter) and initializes the tenant database connection for tenant-scoped routes.

---

## Public Page API

The API exposes published page data to external website frontends. No authentication is required. The website is identified via the `X-Website-Domain` header or `?domain=` query parameter.

```
GET /api/v1/pages                    # List all published pages for a website
GET /api/v1/pages/homepage           # Get the homepage for a website
GET /api/v1/pages/{slug}             # Get a specific page by slug
POST /api/websites/{website}/sync-page  # Sync a page from a tenant database (uses tenant middleware)
```

---

## Artisan Commands

```bash
php artisan make:admin               # Create an administrator account
php artisan app:sync-central-pages   # Sync unlinked central pages to tenant databases
                                     # Accepts --website_id= to target a specific website
```

---

## Project Structure

```text
app/
├── Console/Commands/       # make:admin, app:sync-central-pages
├── Exceptions/             # TenantDatabaseConnectionException, WebsiteProvisioningException
├── Http/
│   ├── Controllers/        # Web controllers, Auth/, and Api/PageApiController
│   ├── Middleware/         # TenantConnection middleware ('tenant' alias)
│   └── Requests/           # Form request validation classes
├── Models/                 # Website (Tenant), Domain, WebsiteDatabaseConnection, Page, etc.
├── Observers/              # Page, PageSection, and Metadata observers
├── Providers/              # AppServiceProvider, TenancyServiceProvider
├── Services/               # WebsiteProvisioningService, PageSyncService
└── Tenancy/Bootstrappers/  # WebsiteDatabaseTenancyBootstrapper
config/
├── database.php            # Includes 'codebasehub', 'mysql', 'sqlite', etc.
└── tenancy.php             # Stancl/Tenancy configuration
database/
├── migrations/             # Central database migrations
├── tenant_migrations/      # Tenant database migrations (pages, media, etc.)
└── seeders/                # DatabaseSeeder, WebsiteSeeder
routes/
├── web.php                 # Admin console routes and /site-page/{slug?}
├── api.php                 # Public /api/v1/pages routes and tenant sync endpoint
└── tenant.php              # Stancl domain-identified tenant routes
```

---

## Requirements

- **PHP**: `^8.3` with extensions: `pdo`, `pdo_mysql` (or `pdo_sqlite`), `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `curl`
- **Composer**: `^2.2`
- **Database**: MySQL 8.0+ / MariaDB 10.4+, or SQLite 3.35+
- **Node.js & npm** (optional, for Vite/Tailwind asset builds)

---

## Installation & Setup

### 1. Install PHP Dependencies

```bash
composer install
```

### 2. Configure the Environment

Copy `.env.example` to `.env` and generate an application key:

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure the Database

`.env.example` defaults to SQLite (`DB_CONNECTION=sqlite`), which works out of the box with `database/database.sqlite`.

To use MySQL, create a database (for example `codebasehub`):

```sql
CREATE DATABASE codebasehub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=codebasehub
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Run Migrations

```bash
php artisan migrate
```

To run tenant migrations on all provisioned websites (requires tenant database credentials to be configured):

```bash
php artisan tenants:migrate
```

### 5. Create an Admin Account

```bash
php artisan make:admin
```

Non-interactive:

```bash
php artisan make:admin --name="Admin User" --email="admin@example.com" --password="your-secure-password"
```

### 6. Optional: Seed Sample Data

```bash
php artisan db:seed
```

Or seed only sample websites:

```bash
php artisan db:seed --class=WebsiteSeeder
```

### 7. Optional: Build Frontend Assets

```bash
npm install
npm run build
```

---

## Running the Application Locally

```bash
php artisan serve
```

The app runs at `http://127.0.0.1:8000`:
- Login: `http://127.0.0.1:8000/login`
- Dashboard: `http://127.0.0.1:8000/dashboard`
- Websites: `http://127.0.0.1:8000/websites`

---

## Testing

Tests run against an in-memory SQLite database (`:memory:`) and do not affect your local database.

```bash
php artisan test
```

Run a single file:

```bash
php artisan test tests/Feature/StanclTenancyDatabaseConnectionTest.php
```

---

## Code Formatting

```bash
vendor/bin/pint
```

---

## Important Development Notes

- **Encrypted Tenant Credentials**: Passwords in `website_database_connections` are encrypted using `APP_KEY`. Changing the key will make existing tenant database passwords unreadable.
- **Generic by Design**: All websites use the same code paths. Adding a new website and connecting a tenant database is enough — no code changes are required.
- **Tenant Route Ordering**: Do not register a `/` route in `routes/tenant.php`, as it would shadow the central dashboard route.
- **`db:seed` Command**: `TenancyServiceProvider` explicitly re-registers Laravel's `db:seed` command so it is not overridden by `stancl/tenancy` on Laravel 13.
