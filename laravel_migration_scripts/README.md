# Laravel database migrations

These 11 migration files were generated from the supplied ER diagram.

## Install
Copy the PHP files into your Laravel project's `database/migrations/` directory, then run:

```bash
php artisan migrate
```

## Notes
- Migrations are ordered so referenced tables are created before dependent tables.
- `status` columns are unsigned tiny integers with default `1`, representing the diagram's `0 or 1`.
- Foreign keys use cascade deletion for website/page-owned records. `websites.customer_id` and `metadata.page_id` are nullable and use `nullOnDelete()`.
- The diagram does not specify a database engine or explicit foreign-key deletion behavior; adjust these choices if your application requires different behavior.
