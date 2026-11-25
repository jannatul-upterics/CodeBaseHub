<?php

namespace App\Console\Commands;

use App\Http\Middleware\TenantConnection;
use App\Models\Website;
use App\Services\PageSyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:sync-central-pages {--website_id= : Sync only a specific website ID}')]
#[Description('Poll central CodeBaseHub DB for unlinked pages and sync them into each website tenant DB')]
class SyncCentralPages extends Command
{
    public function handle(PageSyncService $service): int
    {
        $centralConn = TenantConnection::getCentralConnection();
        $cbhDb = DB::connection($centralConn);

        $websitesQuery = Website::on($centralConn)->whereHas('databaseConnection');
        if ($websiteId = $this->option('website_id')) {
            $websitesQuery->where('id', $websiteId);
        }

        $websites = $websitesQuery->with('databaseConnection')->get();

        if ($websites->isEmpty()) {
            $this->info('No websites with configured tenant database connections found.');

            return self::SUCCESS;
        }

        $totalSynced = 0;

        foreach ($websites as $website) {
            if (! TenantConnection::configure($website)) {
                $this->warn("Skipping website [{$website->website_name}] (ID {$website->id}): tenant connection could not be configured.");

                continue;
            }

            $orphanPages = $cbhDb->table('pages')
                ->where('website_id', $website->id)
                ->whereNull('deleted_at')
                ->whereNull('tenant_page_id')
                ->get();

            foreach ($orphanPages as $centralPage) {
                if ($service->syncFromCodeBaseHub($centralPage)) {
                    $totalSynced++;
                }
            }
        }

        $this->info("Synced {$totalSynced} new central pages into tenant DBs.");

        return self::SUCCESS;
    }
}
