<?php

namespace App\Http\Middleware;

use App\Models\Domain;
use App\Models\Website;
use App\Models\WebsiteDatabaseConnection;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TenantConnection
{
    /**
     * Stored central database connection name.
     */
    public static ?string $centralConnection = null;

    /**
     * Retrieve the central database connection name.
     */
    public static function getCentralConnection(): string
    {
        if (app()->environment('testing')) {
            $current = (string) Config::get('database.default', 'sqlite');

            return $current !== 'tenant' ? $current : 'sqlite';
        }

        if (self::$centralConnection !== null && self::$centralConnection !== 'tenant') {
            return self::$centralConnection;
        }

        if (config('database.connections.codebasehub')) {
            return 'codebasehub';
        }

        $current = (string) Config::get('database.default', 'sqlite');

        return $current !== 'tenant' ? $current : 'sqlite';
    }

    /**
     * Build the connection configuration array.
     *
     * @return array<string, mixed>
     */
    public static function buildConnectionConfig(
        string $driver,
        string $host,
        int $port,
        string $database,
        string $username,
        ?string $password
    ): array {
        $isSqlite = $driver === 'sqlite' || str_ends_with($database, '.sqlite') || $database === ':memory:';

        if ($isSqlite) {
            return [
                'driver' => 'sqlite',
                'database' => $database,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ];
        }

        return [
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

    /**
     * Configure runtime tenant database connection.
     *
     * @param  Website|WebsiteDatabaseConnection|array<string, mixed>  $connection
     */
    public static function configure(
        Website|WebsiteDatabaseConnection|array $connection,
        bool $switchDefault = false
    ): bool {
        if ($connection instanceof Website) {
            $connModel = $connection->databaseConnection;
            if (! $connModel) {
                return false;
            }
            $connection = $connModel;
        }

        if ($connection instanceof WebsiteDatabaseConnection) {
            $driver = (string) ($connection->driver ?: 'mysql');
            $host = (string) ($connection->host ?: '127.0.0.1');
            $port = (int) ($connection->port ?: 3306);
            $database = (string) $connection->database;
            $username = (string) ($connection->username ?: 'root');
            $password = (string) ($connection->password ?: '');
        } else {
            $driver = (string) ($connection['driver'] ?? 'mysql');
            $host = (string) ($connection['host'] ?? '127.0.0.1');
            $port = (int) ($connection['port'] ?? 3306);
            $database = (string) ($connection['database'] ?? '');
            $username = (string) ($connection['username'] ?? 'root');
            $password = (string) ($connection['password'] ?? '');
        }

        $tenantConfig = self::buildConnectionConfig($driver, $host, $port, $database, $username, $password);

        Config::set('database.connections.tenant', $tenantConfig);
        DB::purge('tenant');
        DB::reconnect('tenant');

        if ($switchDefault) {
            if (self::$centralConnection === null && Config::get('database.default') !== 'tenant') {
                self::$centralConnection = Config::get('database.default');
            }
            Config::set('database.default', 'tenant');
        }

        return true;
    }

    /**
     * Configure raw connection parameters.
     */
    public static function configureRaw(
        string $driver,
        string $host,
        int $port,
        string $database,
        string $username,
        ?string $password,
        bool $switchDefault = false
    ): void {
        self::configure([
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
        ], $switchDefault);
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $rawHost = (string) ($request->header('X-Tenant-Domain')
            ?: $request->header('X-Domain')
            ?: $request->header('Host')
            ?: $request->getHost());

        $host = explode(':', $rawHost)[0];

        // Resolve domain → website
        $domain = Domain::where('domain_name', $host)->first();
        $website = $domain?->website;

        if (! $website && $request->route('website')) {
            $websiteParam = $request->route('website');
            $website = $websiteParam instanceof Website ? $websiteParam : Website::find($websiteParam);
        }

        if (! $website) {
            return response()->json(['error' => 'Domain not found'], 404);
        }

        // Retrieve tenant DB connection configuration
        $conn = WebsiteDatabaseConnection::where('website_id', $website->id)->first();
        if (! $conn) {
            return response()->json(['error' => 'Tenant DB not configured'], 500);
        }

        $previousDefault = Config::get('database.default');

        self::configure($conn, switchDefault: true);

        // Share website model for downstream use
        $request->attributes->set('website', $website);

        try {
            return $next($request);
        } finally {
            // Restore default database connection to prevent leaking tenant default to central operations
            Config::set('database.default', $previousDefault);
        }
    }
}
