<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\PageSyncService;

#[Signature('app:sync-central-pages')]
#[Description('Poll central CodeBaseHub DB for new pages and sync them into the Vocabot tenant DB')]
class SyncCentralPages extends Command
{
    public function handle(PageSyncService $service): int
    {
        // Get the Vocabot website record from the central DB
        $cbhDb = DB::connection('codebasehub');
        $website = $cbhDb->table('websites')
            ->where('website_name', 'Vocabot')
            ->orWhere('domain', 'vocabots.com')
            ->orWhere('domain', 'like', '%vocabot%')
            ->first();

        if (! $website) {
            $this->error('Vocabot website not found in central DB');
            return 1;
        }

        // Central pages for this website that are NOT yet linked to a tenant page
        $orphanPages = $cbhDb->table('pages')
            ->where('website_id', $website->id)
            ->whereNull('tenant_page_id')
            ->get();

        foreach ($orphanPages as $centralPage) {
            $service->syncFromCodeBaseHub($centralPage);
        }

        $this->info('Synced '.count($orphanPages).' new central pages into tenant DB');
        return 0;
    }
}
