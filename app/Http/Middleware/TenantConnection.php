<?php

namespace App\Http\Middleware;

use App\Models\Domain;
use App\Models\WebsiteDatabaseConnection;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantConnection
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        // Resolve domain → website
        $domain = Domain::where('domain_name', $host)->first();
        if (! $domain) {
            return response()->json(['error' => 'Domain not found'], 404);
        }
        $website = $domain->website;
        if (! $website) {
            return response()->json(['error' => 'Website not linked to domain'], 500);
        }

        // Retrieve tenant DB connection configuration
        $conn = WebsiteDatabaseConnection::where('website_id', $website->id)->first();
        if (! $conn) {
            return response()->json(['error' => 'Tenant DB not configured'], 500);
        }

        // Build runtime connection config
        $tenantConfig = [
            'driver'   => $conn->driver,
            'host'     => $conn->host,
            'port'     => $conn->port,
            'database' => $conn->database,
            'username' => $conn->username,
            'password' => $conn->password, // encrypted cast returns decrypted
            'charset'  => 'utf8mb4',
            'collation'=> 'utf8mb4_unicode_ci',
            'prefix'   => '',
            'strict'   => true,
            'engine'   => null,
        ];

        // Register temporary "tenant" connection
        Config::set('database.connections.tenant', $tenantConfig);
        DB::purge('tenant');
        DB::reconnect('tenant');

        // Switch default for this request scope
        Config::set('database.default', 'tenant');

        // Share website model for downstream use
        $request->attributes->set('website', $website);

        return $next($request);
    }
}
