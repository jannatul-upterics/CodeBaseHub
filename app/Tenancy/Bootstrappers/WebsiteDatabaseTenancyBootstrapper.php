<?php

declare(strict_types=1);

namespace App\Tenancy\Bootstrappers;

use App\Exceptions\TenantDatabaseConnectionException;
use App\Models\Website;
use App\Models\WebsiteDatabaseConnection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Tenancy;
use Throwable;

class WebsiteDatabaseTenancyBootstrapper implements TenancyBootstrapper
{
    /**
     * Central connection name captured prior to switching to the tenant connection.
     */
    protected ?string $centralConnection = null;

    /**
     * Bootstrap the tenant database connection using credentials from website_database_connections.
     *
     * @throws TenantDatabaseConnectionException
     */
    public function bootstrap(Tenant $tenant): void
    {
        if (! $tenant instanceof Website) {
            throw new TenantDatabaseConnectionException('Invalid tenant model. Expected App\\Models\\Website.');
        }

        $currentDefault = (string) Config::get('database.default');
        if ($currentDefault !== '' && $currentDefault !== 'tenant') {
            $this->centralConnection = $currentDefault;
        }

        $rawPassword = null;

        try {
            $connection = $this->resolveConnectionRecord($tenant);
            $rawPassword = (string) ($connection->password ?? '');
            $this->validateConnectionDetails($connection, $tenant);

            $tenantConfig = $this->buildConnectionConfig($connection);

            $this->purgeTenantConnection();
            Config::set('database.connections.tenant', $tenantConfig);
            DB::reconnect('tenant');

            // Verify connectivity before switching the default connection.
            DB::connection('tenant')->getPdo();

            Config::set('database.default', 'tenant');
            DB::setDefaultConnection('tenant');
        } catch (Throwable $e) {
            $this->cleanupFailedConnection();

            $sanitizedMessage = $this->sanitizeErrorMessage($e->getMessage(), $rawPassword);

            Log::error('Failed to initialize tenant database connection', [
                'website_id' => $tenant->getTenantKey(),
                'error' => $sanitizedMessage,
            ]);

            if ($e instanceof TenantDatabaseConnectionException) {
                throw $e;
            }

            throw new TenantDatabaseConnectionException(
                "Unable to connect to tenant database for website [{$tenant->getTenantKey()}]: {$sanitizedMessage}",
                0,
                $e
            );
        }
    }

    /**
     * Revert the default database connection back to the central database.
     */
    public function revert(): void
    {
        $this->purgeTenantConnection();

        $central = $this->resolveCentralConnection();
        Config::set('database.default', $central);
        DB::setDefaultConnection($central);

        $this->centralConnection = null;
    }

    /**
     * Retrieve the WebsiteDatabaseConnection record for the given Website tenant.
     *
     * @throws TenantDatabaseConnectionException
     */
    protected function resolveConnectionRecord(Website $website): WebsiteDatabaseConnection
    {
        $connection = $website->relationLoaded('databaseConnection')
            ? $website->databaseConnection
            : $website->databaseConnection()->first();

        if (! $connection instanceof WebsiteDatabaseConnection) {
            throw new TenantDatabaseConnectionException(
                "Tenant database connection is not configured for website [{$website->getTenantKey()}]."
            );
        }

        if (trim((string) $connection->database) === '') {
            throw new TenantDatabaseConnectionException(
                "Tenant database name is missing for website [{$website->getTenantKey()}]."
            );
        }

        return $connection;
    }

    /**
     * Build a Laravel database connection configuration array from a WebsiteDatabaseConnection model or array.
     *
     * @param  WebsiteDatabaseConnection|array<string, mixed>  $connection
     * @return array<string, mixed>
     */
    public function buildConnectionConfig(WebsiteDatabaseConnection|array $connection): array
    {
        if ($connection instanceof WebsiteDatabaseConnection) {
            $driver = strtolower(trim((string) ($connection->driver ?: 'mysql')));
            $database = (string) $connection->database;
            $host = (string) ($connection->host ?: '127.0.0.1');
            $port = (int) ($connection->port ?: $this->defaultPortForDriver($driver));
            $username = (string) ($connection->username ?? '');
            $password = (string) ($connection->password ?? '');
        } else {
            $driver = strtolower(trim((string) ($connection['driver'] ?? 'mysql')));
            $database = (string) ($connection['database'] ?? '');
            $host = (string) ($connection['host'] ?? '127.0.0.1');
            $port = (int) ($connection['port'] ?? $this->defaultPortForDriver($driver));
            $username = (string) ($connection['username'] ?? '');
            $password = (string) ($connection['password'] ?? '');
        }

        if ($driver === 'sqlite' || str_ends_with($database, '.sqlite') || $database === ':memory:') {
            return [
                'driver' => 'sqlite',
                'database' => $database,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ];
        }

        if ($driver === 'pgsql') {
            return [
                'driver' => 'pgsql',
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password,
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode' => 'prefer',
            ];
        }

        if ($driver === 'sqlsrv') {
            return [
                'driver' => 'sqlsrv',
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password,
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
            ];
        }

        return [
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ];
    }

    /**
     * Get the default port for a database driver.
     */
    protected function defaultPortForDriver(string $driver): int
    {
        return match ($driver) {
            'pgsql' => 5432,
            'sqlsrv' => 1433,
            default => 3306,
        };
    }

    /**
     * Clean up any partial tenant connection state and end tenancy on failure.
     */
    protected function cleanupFailedConnection(): void
    {
        $this->purgeTenantConnection();

        $central = $this->resolveCentralConnection();
        Config::set('database.default', $central);
        DB::setDefaultConnection($central);

        if (app()->bound(Tenancy::class)) {
            $tenancy = app(Tenancy::class);
            if ($tenancy->initialized) {
                $tenancy->initialized = false;
                $tenancy->tenant = null;
            }
        }
    }

    /**
     * Purge the tenant database connection and remove its runtime configuration.
     */
    protected function purgeTenantConnection(): void
    {
        if (array_key_exists('tenant', DB::getConnections())) {
            DB::purge('tenant');
        }

        Config::offsetUnset('database.connections.tenant');
    }

    /**
     * Resolve the central database connection name.
     */
    public function resolveCentralConnection(): string
    {
        if ($this->centralConnection !== null && $this->centralConnection !== 'tenant') {
            return $this->centralConnection;
        }

        $configured = (string) Config::get('tenancy.database.central_connection', '');
        if ($configured !== '' && $configured !== 'tenant' && Config::has("database.connections.{$configured}")) {
            return $configured;
        }

        return Config::has('database.connections.codebasehub') ? 'codebasehub' : 'sqlite';
    }

    /**
     * Supported database drivers for tenant connections.
     *
     * @var list<string>
     */
    protected const SUPPORTED_DRIVERS = ['mysql', 'mariadb', 'pgsql', 'sqlsrv', 'sqlite'];

    /**
     * Validate database connection details before attempting to connect.
     *
     * @throws TenantDatabaseConnectionException
     */
    protected function validateConnectionDetails(WebsiteDatabaseConnection $connection, Website $website): void
    {
        $driver = strtolower(trim((string) ($connection->driver ?: 'mysql')));
        if (! in_array($driver, self::SUPPORTED_DRIVERS, true)) {
            throw new TenantDatabaseConnectionException(
                "Unsupported database driver [{$driver}] for website [{$website->getTenantKey()}]."
            );
        }

        $database = (string) $connection->database;
        if (str_contains($database, "\0")) {
            throw new TenantDatabaseConnectionException(
                "Invalid database name for website [{$website->getTenantKey()}]."
            );
        }

        $isSqlite = $driver === 'sqlite' || str_ends_with($database, '.sqlite') || $database === ':memory:';
        if ($isSqlite) {
            return;
        }

        if (! preg_match('/^[a-zA-Z0-9_.\-]+$/', $database)) {
            throw new TenantDatabaseConnectionException(
                "Invalid database name for website [{$website->getTenantKey()}]."
            );
        }

        $host = trim((string) $connection->host);
        if ($host === '' || preg_match('/[\s;\x00-\x1F]/', $host)) {
            throw new TenantDatabaseConnectionException(
                "Invalid database host for website [{$website->getTenantKey()}]."
            );
        }

        $port = $connection->port;
        if ($port !== null && ((int) $port < 1 || (int) $port > 65535)) {
            throw new TenantDatabaseConnectionException(
                "Invalid database port for website [{$website->getTenantKey()}]."
            );
        }

        if (trim((string) $connection->username) === '') {
            throw new TenantDatabaseConnectionException(
                "Database username is required for website [{$website->getTenantKey()}]."
            );
        }
    }

    /**
     * Sanitize error messages to prevent credential leakage.
     */
    protected function sanitizeErrorMessage(string $message, ?string $rawPassword = null): string
    {
        if ($rawPassword !== null && $rawPassword !== '') {
            $message = str_replace($rawPassword, '••••••••', $message);
        }

        $message = (string) preg_replace('/(password|pwd)[=:]\s*([^\s;]+)/i', '$1=••••••••', $message);
        $message = (string) preg_replace('/:([^:@\/\s]+)@/', ':••••••••@', $message);

        return $message;
    }
}
