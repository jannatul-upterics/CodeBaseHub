<?php
/**
 * End-to-End Test: Create page in CodeBaseHub → Verify on Vocabot
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Page;
use App\Models\Website;
use App\Services\PageSyncService;

$slug = 'test-page-' . time();
echo "=== E2E TEST: Creating page with slug: {$slug} ===\n\n";

// 1. Find the Vocabot website
$website = Website::find(6);
echo "Website: {$website->website_name} (ID: {$website->id})\n";
echo "DB Connection: ".($website->databaseConnection ? 'YES (DB='.$website->databaseConnection->database.')' : 'NO')."\n\n";

// 2. Create the page in CodeBaseHub
$page = $website->pages()->create([
    'title'       => 'Test Page ' . date('H:i:s'),
    'slug'        => $slug,
    'page_type'   => 'standard',
    'is_homepage' => false,
    'status'      => 1,
    'content'     => 'This page was created via the CodeBaseHub E2E test at ' . now(),
]);
echo "Page created in CodeBaseHub: ID={$page->id}, slug={$page->slug}\n";

// 3. Add a section to the page
$section = $page->sections()->create([
    'section_name' => 'hero',
    'section_type' => 'hero',
    'content'      => json_encode([
        'heading'     => 'Welcome to ' . $page->title,
        'subheading'  => 'Dynamically created from CodeBaseHub',
        'body'        => 'This content was created at '.date('Y-m-d H:i:s').' and synced to Vocabot automatically.',
        'button_text' => 'Learn More',
        'button_url'  => '/contact',
    ]),
    'sort_order'   => 1,
    'status'       => 1,
]);
echo "Section created: ID={$section->id}, name={$section->section_name}\n\n";

// 4. Sync to tenant
$syncService = app(PageSyncService::class);
$page->fresh();
$result = $syncService->syncPageToTenant($page, $website);
echo "Sync to Tenant Result: ".($result ? 'SUCCESS' : 'FAILED')."\n";

// 5. Verify in Vocabot DB
$page->refresh();
echo "tenant_page_id: ".(isset($page->tenant_page_id) ? $page->tenant_page_id : 'N/A')."\n";
echo "sync_hash: ".($page->sync_hash ?? 'N/A')."\n";

// 6. Check Vocabot DB directly
$syncService->configureTenantConnection($website);
$tenantPage = \DB::connection('tenant')->table('pages')->where('slug', $slug)->first();
echo "\n=== Vocabot DB Check ===\n";
if ($tenantPage) {
    echo "FOUND in Vocabot DB: ID={$tenantPage->id}, slug={$tenantPage->slug}, status={$tenantPage->status}\n";
    $tenantSections = \DB::connection('tenant')->table('page_sections')->where('page_id', $tenantPage->id)->get()->toArray();
    echo "Sections in Vocabot: ".count($tenantSections)."\n";
    foreach ($tenantSections as $s) {
        echo "  - {$s->section_name} ({$s->section_type})\n";
    }
} else {
    echo "NOT FOUND in Vocabot DB for slug: {$slug}\n";
}

echo "\n=== TEST URL ===\n";
echo "Visit: http://127.0.0.1:8001/{$slug}\n";
echo "\n=== DONE ===\n";
