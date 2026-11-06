<?php

namespace App\Services;

use App\Exceptions\WebsiteProvisioningException;
use App\Models\Website;
use App\Models\WebsiteDatabaseConnection;
use Closure;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDO;
use PDOException;
use Throwable;

class WebsiteProvisioningService
{
    /**
     * Required tenant tables to verify when connecting an existing database.
     *
     * @var list<string>
     */
    public const REQUIRED_TENANT_TABLES = ['pages', 'media'];

    /**
     * Custom PDO factory for testing / mock injection.
     *
     * @var (Closure(string, string, int, ?string, string, ?string): PDO)|null
     */
    protected ?Closure $pdoFactory = null;

    /**
     * Set a custom PDO factory (useful for tests and mocking server interactions).
     *
     * @param  (Closure(string, string, int, ?string, string, ?string): PDO)|null  $factory
     */
    public function setPdoFactory(?Closure $factory): void
    {
        $this->pdoFactory = $factory;
    }

    /**
     * Test connection credentials before creating or connecting a database.
     *
     * @param  array<string, mixed>  $config
     * @return array{
     *     success: bool,
     *     message: string,
     *     database_exists?: bool,
     *     tables_exist?: bool,
     *     missing_tables?: list<string>
     * }
     */
    public function testConnection(array $config, string $mode = 'create'): array
    {
        $driver = (string) ($config['driver'] ?? config('database.default', 'mysql'));
        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (int) ($config['port'] ?? 3306);
        $database = isset($config['database']) && trim((string) $config['database']) !== '' ? trim((string) $config['database']) : null;
        $username = (string) ($config['username'] ?? 'root');
        $password = (string) ($config['password'] ?? '');

        try {
            // 1. Test server connection (without selecting target database)
            $serverPdo = $this->createPdo($driver, $host, $port, null, $username, $password);
            $isSqlite = $this->isSqlite($serverPdo, $driver);

            if ($database !== null && ! $this->isValidDatabaseName($database, $isSqlite)) {
                return [
                    'success' => false,
                    'message' => 'The database name may only contain alphanumeric characters and underscores (max 64 characters).',
                ];
            }

            if ($mode === 'create') {
                if ($database !== null) {
                    $resolvedDb = $this->resolveDatabaseTarget($database, $driver, $serverPdo);
                    $exists = $this->databaseExists($serverPdo, $resolvedDb, $driver);
                    if ($exists) {
                        return [
                            'success' => false,
                            'message' => "Database '{$database}' already exists on host '{$host}'. Choose a different name or use 'Connect Existing Database'.",
                            'database_exists' => true,
                        ];
                    }

                    return [
                        'success' => true,
                        'message' => "Connection successful. Database '{$database}' is available to be created.",
                        'database_exists' => false,
                    ];
                }

                return [
                    'success' => true,
                    'message' => 'Connection to server successful. A unique database name will be generated automatically.',
                ];
            }

            // Mode: connect
            if ($database === null) {
                return [
                    'success' => false,
                    'message' => 'A database name is required to connect to an existing database.',
                ];
            }

            $resolvedDb = $this->resolveDatabaseTarget($database, $driver, $serverPdo);
            $exists = $this->databaseExists($serverPdo, $resolvedDb, $driver);
            if (! $exists) {
                return [
                    'success' => false,
                    'message' => "Database '{$database}' does not exist on host '{$host}'.",
                    'database_exists' => false,
                ];
            }

            // Test connection directly to the target database and verify permissions
            $dbPdo = $this->createPdo($driver, $host, $port, $resolvedDb, $username, $password);
            $this->verifyUserPermissions($dbPdo, $driver);

            // Check for required tenant tables
            $existingTables = $this->getExistingTables($dbPdo, $resolvedDb, $driver);
            $missingTables = array_values(array_diff(self::REQUIRED_TENANT_TABLES, $existingTables));

            if (empty($missingTables)) {
                return [
                    'success' => true,
                    'message' => "Connection verified. Database '{$database}' exists and contains all required tenant tables.",
                    'database_exists' => true,
                    'tables_exist' => true,
                    'missing_tables' => [],
                ];
            }

            return [
                'success' => true,
                'message' => 'Connection verified. Database exists, but missing tables: '.implode(', ', $missingTables).'. Migrations can be safely applied.',
                'database_exists' => true,
                'tables_exist' => false,
                'missing_tables' => $missingTables,
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => $this->sanitizeErrorMessage($e->getMessage()),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => $this->sanitizeErrorMessage($e->getMessage()),
            ];
        }
    }

    /**
     * Option 1: Create a brand new tenant database and run all tenant migrations.
     *
     * @param  array<string, mixed>  $config
     *
     * @throws WebsiteProvisioningException
     */
    public function provisionNewDatabase(Website $website, array $config = []): WebsiteDatabaseConnection
    {
        $website->update(['provisioning_status' => 'provisioning']);

        $driver = (string) ($config['driver'] ?? config('database.default', 'mysql'));
        $host = (string) ($config['host'] ?? config('database.connections.'.$driver.'.host', '127.0.0.1'));
        $port = (int) ($config['port'] ?? config('database.connections.'.$driver.'.port', 3306));
        $username = (string) ($config['username'] ?? config('database.connections.'.$driver.'.username', 'root'));
        $password = (string) ($config['password'] ?? config('database.connections.'.$driver.'.password', ''));
        $databaseInput = isset($config['database']) && trim((string) $config['database']) !== '' ? trim((string) $config['database']) : null;

        try {
            $serverPdo = $this->createPdo($driver, $host, $port, null, $username, $password);
            $isSqlite = $this->isSqlite($serverPdo, $driver);

            $database = $databaseInput ?? $this->generateUniqueDatabaseName($website, $host, $driver, $serverPdo);

            if (! $this->isValidDatabaseName($database, $isSqlite)) {
                throw new WebsiteProvisioningException("Invalid database name: '{$database}'. Must be alphanumeric and underscores only (max 64 chars).");
            }

            $resolvedDb = $this->resolveDatabaseTarget($database, $driver, $serverPdo);

            // Prevent duplicate database creation
            if ($this->isDatabaseNameTaken($database, $host, $driver, $serverPdo, $website->id)) {
                throw new WebsiteProvisioningException("Database '{$database}' already exists or is already assigned to another website. Choose a different name or use Connect Existing Database.");
            }

            // 1. Create the database physically on the server
            $this->createDatabase($serverPdo, $resolvedDb, $driver);

            // 2. Configure dynamic tenant connection in Laravel config
            $this->configureTenantConnection($driver, $host, $port, $resolvedDb, $username, $password);

            // 3. Run tenant migrations against the newly created database
            try {
                $exitCode = Artisan::call('migrate', [
                    '--path' => 'database/tenant_migrations',
                    '--database' => 'tenant',
                    '--force' => true,
                ]);

                if ($exitCode !== 0) {
                    throw new Exception('Artisan migrate returned exit code '.$exitCode.': '.Artisan::output());
                }
            } catch (Throwable $migrationError) {
                // Safe rollback on failure: drop the newly created database and clean up
                Log::error('Tenant migration failed during new database provisioning, rolling back database', [
                    'website_id' => $website->id,
                    'database' => $database,
                    'error' => $migrationError->getMessage(),
                ]);

                try {
                    $this->dropDatabase($serverPdo, $resolvedDb, $driver);
                } catch (Throwable $dropError) {
                    Log::warning('Could not drop database on rollback', ['database' => $database, 'error' => $dropError->getMessage()]);
                }

                throw $migrationError;
            }

            // 4. Persist connection details in central table
            $connection = WebsiteDatabaseConnection::updateOrCreate(
                ['website_id' => $website->id],
                [
                    'driver' => $driver,
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                    'username' => $username,
                    'password' => $password, // cast to encrypted automatically
                ]
            );

            // 5. Update website status to provisioned and clear any prior error
            $website->update(['provisioning_status' => 'provisioned']);
            $website->setProvisioningError(null);

            return $connection;
        } catch (Throwable $e) {
            $sanitizedError = $this->sanitizeErrorMessage($e->getMessage());
            $website->update(['provisioning_status' => 'failed']);
            $website->setProvisioningError($sanitizedError);

            Log::error('Tenant database provisioning failed', [
                'website_id' => $website->id,
                'error' => $sanitizedError,
            ]);

            throw new WebsiteProvisioningException($sanitizedError, 0, $e);
        }
    }

    /**
     * Option 2: Connect to an existing database safely without dropping or overwriting data.
     *
     * @param  array<string, mixed>  $config
     *
     * @throws WebsiteProvisioningException
     */
    public function connectExistingDatabase(
        Website $website,
        array $config,
        bool $runMigrationsIfMissing = true
    ): WebsiteDatabaseConnection {
        $website->update(['provisioning_status' => 'provisioning']);

        $driver = (string) ($config['driver'] ?? config('database.default', 'mysql'));
        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (int) ($config['port'] ?? 3306);
        $username = (string) ($config['username'] ?? 'root');
        $password = (string) ($config['password'] ?? '');
        $database = isset($config['database']) ? trim((string) $config['database']) : '';

        try {
            $serverPdo = $this->createPdo($driver, $host, $port, null, $username, $password);
            $isSqlite = $this->isSqlite($serverPdo, $driver);

            if ($database === '' || ! $this->isValidDatabaseName($database, $isSqlite)) {
                throw new WebsiteProvisioningException('A valid database name is required to connect to an existing database.');
            }

            $resolvedDb = $this->resolveDatabaseTarget($database, $driver, $serverPdo);

            // 2. Verify that the database actually exists
            if (! $this->databaseExists($serverPdo, $resolvedDb, $driver)) {
                throw new WebsiteProvisioningException("Database '{$database}' does not exist on host '{$host}'.");
            }

            // 3. Verify user permissions on target database
            $dbPdo = $this->createPdo($driver, $host, $port, $resolvedDb, $username, $password);
            $this->verifyUserPermissions($dbPdo, $driver);

            // 4. Configure tenant connection
            $this->configureTenantConnection($driver, $host, $port, $resolvedDb, $username, $password);

            // 5. Check whether required tenant tables already exist
            $existingTables = $this->getExistingTables($dbPdo, $resolvedDb, $driver);
            $missingTables = array_values(array_diff(self::REQUIRED_TENANT_TABLES, $existingTables));

            if (! empty($missingTables)) {
                if ($runMigrationsIfMissing) {
                    // Safe execution of tenant migrations – does not truncate or drop existing tables!
                    $exitCode = Artisan::call('migrate', [
                        '--path' => 'database/tenant_migrations',
                        '--database' => 'tenant',
                        '--force' => true,
                    ]);

                    if ($exitCode !== 0) {
                        throw new Exception('Tenant migrations on existing database failed: '.Artisan::output());
                    }
                } else {
                    throw new WebsiteProvisioningException('Required tenant tables ('.implode(', ', $missingTables).") are missing in database '{$database}'.");
                }
            }

            // 6. Save connection details in central table
            $connection = WebsiteDatabaseConnection::updateOrCreate(
                ['website_id' => $website->id],
                [
                    'driver' => $driver,
                    'host' => $host,
                    'port' => $port,
                    'database' => $database,
                    'username' => $username,
                    'password' => $password,
                ]
            );

            // 7. Update status to provisioned only after successful verification and setup
            $website->update(['provisioning_status' => 'provisioned']);
            $website->setProvisioningError(null);

            return $connection;
        } catch (Throwable $e) {
            // CRITICAL: NEVER drop, truncate, or overwrite existing database
            $sanitizedError = $this->sanitizeErrorMessage($e->getMessage());
            $website->update(['provisioning_status' => 'failed']);
            $website->setProvisioningError($sanitizedError);

            Log::error('Connecting existing tenant database failed', [
                'website_id' => $website->id,
                'database' => $database,
                'error' => $sanitizedError,
            ]);

            throw new WebsiteProvisioningException($sanitizedError, 0, $e);
        }
    }

    /**
     * Retry failed provisioning for a website.
     *
     * @param  array<string, mixed>  $overrideConfig
     *
     * @throws WebsiteProvisioningException
     */
    public function retryProvisioning(Website $website, array $overrideConfig = []): WebsiteDatabaseConnection
    {
        $existingConnection = $website->databaseConnection;
        $mode = $overrideConfig['tenant_db_mode'] ?? ($existingConnection ? 'connect' : 'create');

        $config = [
            'driver' => $overrideConfig['driver'] ?? $existingConnection?->driver ?? config('database.default', 'mysql'),
            'host' => $overrideConfig['db_host'] ?? $overrideConfig['host'] ?? $existingConnection?->host ?? '127.0.0.1',
            'port' => (int) ($overrideConfig['db_port'] ?? $overrideConfig['port'] ?? $existingConnection?->port ?? 3306),
            'database' => $overrideConfig['db_name'] ?? $overrideConfig['database'] ?? $existingConnection?->database ?? null,
            'username' => $overrideConfig['db_username'] ?? $overrideConfig['username'] ?? $existingConnection?->username ?? 'root',
            'password' => $overrideConfig['db_password'] ?? $overrideConfig['password'] ?? $existingConnection?->password ?? '',
        ];

        $runMigrations = (bool) ($overrideConfig['run_migrations'] ?? true);

        if ($mode === 'connect') {
            return $this->connectExistingDatabase($website, $config, $runMigrations);
        }

        return $this->provisionNewDatabase($website, $config);
    }

    /**
     * Dynamically register and configure tenant connection in Laravel.
     */
    public function configureTenantConnection(
        string $driver,
        string $host,
        int $port,
        string $database,
        string $username,
        ?string $password
    ): void {
        $isSqlite = $driver === 'sqlite' || str_ends_with($database, '.sqlite') || $database === ':memory:';

        if ($isSqlite) {
            $config = [
                'driver' => 'sqlite',
                'database' => $database,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ];
        } else {
            $config = [
                'driver' => $driver,
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ];
        }

        Config::set('database.connections.tenant', $config);
        DB::purge('tenant');
    }

    /**
     * Create a PDO instance (or use injected factory if in test mode).
     *
     * @throws PDOException
     */
    public function createPdo(
        string $driver,
        string $host,
        int $port,
        ?string $database,
        string $username,
        ?string $password
    ): PDO {
        if ($this->pdoFactory !== null) {
            return ($this->pdoFactory)($driver, $host, $port, $database, $username, $password);
        }

        if ($driver === 'sqlite') {
            $dbPath = $database ?? ':memory:';

            return new PDO("sqlite:{$dbPath}", null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        }

        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        if ($database !== null && $database !== '') {
            $dsn .= ";dbname={$database}";
        }

        return new PDO($dsn, $username, $password ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
    }

    /**
     * Check if a database exists on the server.
     */
    public function databaseExists(PDO $pdo, string $database, string $driver = 'mysql'): bool
    {
        if ($this->isSqlite($pdo, $driver)) {
            return $database === ':memory:' || file_exists($database);
        }

        $stmt = $pdo->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
        $stmt->execute([$database]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Create a database on the server.
     */
    public function createDatabase(PDO $pdo, string $database, string $driver = 'mysql'): void
    {
        if ($this->isSqlite($pdo, $driver)) {
            if ($database !== ':memory:') {
                $dir = dirname($database);
                if (! is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                touch($database);
            }

            return;
        }

        $safeName = str_replace('`', '``', $database);
        $pdo->exec("CREATE DATABASE `{$safeName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    /**
     * Drop a database on the server (used only during rollback of brand new creation).
     */
    public function dropDatabase(PDO $pdo, string $database, string $driver = 'mysql'): void
    {
        if ($this->isSqlite($pdo, $driver)) {
            if ($database !== ':memory:' && file_exists($database)) {
                @unlink($database);
            }

            return;
        }

        $safeName = str_replace('`', '``', $database);
        $pdo->exec("DROP DATABASE IF EXISTS `{$safeName}`");
    }

    /**
     * Verify database user permissions (SELECT and table operations).
     *
     * @throws PDOException|WebsiteProvisioningException
     */
    public function verifyUserPermissions(PDO $pdo, string $driver = 'mysql'): void
    {
        // 1. Verify read
        $pdo->query('SELECT 1');

        // 2. Verify table operations safely using temporary table
        if ($this->isSqlite($pdo, $driver)) {
            $pdo->exec('CREATE TEMPORARY TABLE __cbh_perm_check (id INTEGER PRIMARY KEY)');
            $pdo->exec('DROP TABLE __cbh_perm_check');
        } else {
            $pdo->exec('CREATE TEMPORARY TABLE __cbh_perm_check (id INT)');
            $pdo->exec('DROP TEMPORARY TABLE __cbh_perm_check');
        }
    }

    /**
     * Retrieve list of existing tables in the database.
     *
     * @return list<string>
     */
    public function getExistingTables(PDO $pdo, string $database, string $driver = 'mysql'): array
    {
        if ($this->isSqlite($pdo, $driver)) {
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

            return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        }

        $stmt = $pdo->prepare('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?');
        $stmt->execute([$database]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Generate a unique database name for a website.
     * Example: codebasehub_acme_site_12
     */
    public function generateUniqueDatabaseName(
        Website $website,
        string $host,
        string $driver = 'mysql',
        ?PDO $pdo = null
    ): string {
        $source = $website->domain ?: ($website->website_name ?: 'site');
        $slug = Str::slug($source, '_');
        $slug = substr(preg_replace('/[^a-zA-Z0-9_]/', '', $slug) ?: 'site', 0, 32);

        $base = "cbh_{$slug}_{$website->id}";
        $candidate = substr($base, 0, 64);

        $counter = 1;
        while ($this->isDatabaseNameTaken($candidate, $host, $driver, $pdo, $website->id)) {
            $candidate = substr("cbh_{$slug}_{$website->id}_{$counter}", 0, 64);
            $counter++;
        }

        return $candidate;
    }

    /**
     * Check if a database name is already in use by another website or exists physically.
     */
    public function isDatabaseNameTaken(
        string $database,
        string $host,
        string $driver = 'mysql',
        ?PDO $pdo = null,
        ?int $excludeWebsiteId = null
    ): bool {
        // Check central website_database_connections records
        $query = WebsiteDatabaseConnection::where('host', $host)
            ->where('database', $database);

        if ($excludeWebsiteId !== null) {
            $query->where('website_id', '!=', $excludeWebsiteId);
        }

        if ($query->exists()) {
            return true;
        }

        // Check if database physically exists on the server
        if ($pdo !== null) {
            try {
                $resolvedDb = $this->resolveDatabaseTarget($database, $driver, $pdo);

                return $this->databaseExists($pdo, $resolvedDb, $driver);
            } catch (Throwable) {
                // If checking fails, proceed safely
            }
        }

        return false;
    }

    /**
     * Determine if PDO connection or driver is SQLite.
     */
    public function isSqlite(?PDO $pdo = null, string $driver = 'mysql'): bool
    {
        if ($driver === 'sqlite') {
            return true;
        }

        if ($pdo !== null) {
            try {
                return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
            } catch (Throwable) {
                return false;
            }
        }

        return false;
    }

    /**
     * Validate database name formatting.
     */
    public function isValidDatabaseName(string $name, bool $isSqlite = false): bool
    {
        if ($isSqlite) {
            return strlen($name) > 0 && strlen($name) < 255;
        }

        return (bool) preg_match('/^[a-zA-Z0-9_]{1,64}$/', $name);
    }

    /**
     * Resolve database target (maps to file path if sqlite, or leaves as identifier for mysql).
     */
    public function resolveDatabaseTarget(string $database, string $driver = 'mysql', ?PDO $pdo = null): string
    {
        if ($this->isSqlite($pdo, $driver)) {
            if ($database === ':memory:' || str_contains($database, '/') || str_contains($database, '\\')) {
                return $database;
            }

            return storage_path("framework/testing/tenant_dbs/{$database}.sqlite");
        }

        return $database;
    }

    /**
     * Sanitize error message to prevent accidental credential or password leakage.
     */
    protected function sanitizeErrorMessage(string $message): string
    {
        $clean = preg_replace('/(password|pwd)[=:]\s*([^\s;]+)/i', '$1=••••••••', $message);

        return (string) $clean;
    }
}
