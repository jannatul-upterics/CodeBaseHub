<?php

namespace App\Http\Middleware;

use App\Exceptions\TenantDatabaseConnectionException;
use App\Models\Domain;
use App\Models\Website;
use App\Models\WebsiteDatabaseConnection;
use App\Tenancy\Bootstrappers\WebsiteDatabaseTenancyBootstrapper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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

        return app(WebsiteDatabaseTenancyBootstrapper::class)->resolveCentralConnection();
    }

    /**
     * Build the connection configuration array via WebsiteDatabaseTenancyBootstrapper.
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
        return app(WebsiteDatabaseTenancyBootstrapper::class)->buildConnectionConfig([
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password ?? '',
        ]);
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

        $tenantConfig = app(WebsiteDatabaseTenancyBootstrapper::class)->buildConnectionConfig($connection);

        Config::set('database.connections.tenant', $tenantConfig);
        DB::purge('tenant');
        DB::reconnect('tenant');

        if ($switchDefault) {
            if (self::$centralConnection === null && Config::get('database.default') !== 'tenant') {
                self::$centralConnection = (string) Config::get('database.default');
            }
            Config::set('database.default', 'tenant');
            DB::setDefaultConnection('tenant');
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

        $host = strtolower(explode(':', $rawHost)[0]);

        $website = Domain::where('domain', $host)->first()?->website;

        if (! $website && $request->route('website')) {
            $websiteParam = $request->route('website');
            $website = $websiteParam instanceof Website ? $websiteParam : Website::find($websiteParam);
        }

        if (! $website) {
            return response()->json(['error' => 'Domain not found'], 404);
        }

        if (! $website->databaseConnection()->exists()) {
            return response()->json(['error' => 'Tenant DB not configured'], 500);
        }

        $bootstrapper = app(WebsiteDatabaseTenancyBootstrapper::class);

        try {
            $bootstrapper->bootstrap($website);
        } catch (TenantDatabaseConnectionException|Throwable $e) {
            Log::error('TenantConnection middleware failed to connect', [
                'website_id' => $website->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Tenant database connection failed'], 503);
        }

        $request->attributes->set('website', $website);

        try {
            return $next($request);
        } finally {
            $bootstrapper->revert();
        }
    }
}
