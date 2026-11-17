<?php
// Bootstrap CodeBaseHub
define('LARAVEL_START', microtime(true));
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Page;
use App\Models\PageSection;
use App\Models\Website;
use App\Services\PageSyncService;
use App\Services\DatabaseConnectionService;
use Illuminate\Support\Facades\DB;

$slug = 'e2e-test-final';

echo "=== Cleanup any existing test page ===" . PHP_EOL;
Page::where('slug', $slug)->delete();

echo PHP_EOL . "=== Step 1: Create page in CodeBaseHub (website_id=6 = Vocabot) ===" . PHP_EOL;
$page = Page::create([
    'website_id' => 6,
    'title' => 'E2E Sync Test Page',
    'slug' => $slug,
    'page_type' => 'standard',
    'status' => 1,
    'content' => 'Synced from CodeBaseHub to Vocabot.',
    'meta_title' => 'E2E Sync Test | Vocabot',
    'meta_description' => 'End-to-end page sync verification test.',
]);
echo "Created codebasehub.pages ID={$page->id}, slug={$page->slug}" . PHP_EOL;

echo PHP_EOL . "=== Step 2: Create a CTA section ===" . PHP_EOL;
$section = PageSection::create([
    'page_id' => $page->id,
    'section_name' => 'cta',
    'section_type' => 'cta',
    'content' => json_encode([
        'heading' => 'Get Started Today',
        'button_text' => 'Start Free Trial',
        'button_url' => '/contact',
    ]),
    'sort_order' => 1,
    'status' => 1,
]);
echo "Section created: ID={$section->id}" . PHP_EOL;

echo PHP_EOL . "=== Step 3: Trigger manual sync to vocabot DB ===" . PHP_EOL;
$syncService = app(PageSyncService::class);
$page->load('sections');
$ok = $syncService->syncPageToTenant($page);
echo "Sync result: " . ($ok ? "SUCCESS" : "FAILED") . PHP_EOL;

echo PHP_EOL . "=== Step 4: Get vocabot DB connection and check record ===" . PHP_EOL;
$website = Website::with('databaseConnection')->find(6);
$dbConn = $website->databaseConnection;
echo "Website: {$website->website_name}" . PHP_EOL;
echo "DB connection configured: " . ($dbConn ? $dbConn->connection_name : 'NONE') . PHP_EOL;

// Build the vocabot connection config
$dbService = app(DatabaseConnectionService::class);
$connName = $dbService->getConnectionName($website);
echo "Using connection: {$connName}" . PHP_EOL;

$vocabotPage = DB::connection($connName)->table('pages')->where('slug', $slug)->first();
if ($vocabotPage) {
    echo "SYNCED: vocabot.pages ID={$vocabotPage->id}, title={$vocabotPage->title}" . PHP_EOL;
    $secCount = DB::connection($connName)->table('page_sections')->where('page_id', $vocabotPage->id)->count();
    echo "Sections in vocabot.page_sections: {$secCount}" . PHP_EOL;
} else {
    echo "NOT synced to vocabot DB." . PHP_EOL;
}

echo PHP_EOL . "=== Step 5: Hit Vocabot frontend at http://127.0.0.1:8001/{$slug} ===" . PHP_EOL;
$url = "http://127.0.0.1:8001/{$slug}";
$ctx = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
$html = @file_get_contents($url, false, $ctx);
$statusLine = $http_response_header[0] ?? 'No response';
preg_match('/HTTP\/\S+ (\d+)/', $statusLine, $m);
$status = (int)($m[1] ?? 0);
echo "HTTP Status: {$status}" . PHP_EOL;

if ($status === 200) {
    echo "PASS: Page loads at {$url}" . PHP_EOL;
    if (str_contains($html, 'E2E Sync Test Page')) {
        echo "h1 title visible: YES" . PHP_EOL;
    } else {
        preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $html, $hm);
        echo "h1 content: " . trim(strip_tags($hm[0] ?? 'not found')) . PHP_EOL;
    }
    echo "Content visible: " . (str_contains($html, 'Synced from CodeBaseHub') ? 'YES' : 'NO') . PHP_EOL;
    echo "CTA heading visible: " . (str_contains($html, 'Get Started Today') ? 'YES' : 'NO') . PHP_EOL;
} else {
    echo "FAIL: Status {$status}" . PHP_EOL;
    echo "Response: " . substr($html ?: '', 0, 300) . PHP_EOL;
}

echo PHP_EOL . "=== Step 6: Update the page in CodeBaseHub ===" . PHP_EOL;
$page->update([
    'title' => 'E2E Sync Test Page (Updated)',
    'content' => 'UPDATED: Content reflects the update from CodeBaseHub.',
]);
echo "Updated title to: {$page->title}" . PHP_EOL;

echo PHP_EOL . "=== Step 7: Check update synced to vocabot DB ===" . PHP_EOL;
$vocabotPageUpdated = DB::connection($connName)->table('pages')->where('slug', $slug)->first();
$syncedTitle = $vocabotPageUpdated->title ?? 'NOT FOUND';
echo "vocabot.pages title: {$syncedTitle}" . PHP_EOL;
echo "Update synced: " . ($syncedTitle === 'E2E Sync Test Page (Updated)' ? 'YES' : 'NO') . PHP_EOL;

echo PHP_EOL . "=== Step 8: Reload Vocabot page for updated content ===" . PHP_EOL;
$html2 = @file_get_contents($url, false, $ctx);
$statusLine2 = $http_response_header[0] ?? '';
preg_match('/HTTP\/\S+ (\d+)/', $statusLine2, $m2);
$status2 = (int)($m2[1] ?? 0);
echo "HTTP Status: {$status2}" . PHP_EOL;
if ($status2 === 200) {
    echo "Updated title visible: " . (str_contains($html2, 'E2E Sync Test Page (Updated)') ? 'YES' : 'NO') . PHP_EOL;
    echo "Updated content visible: " . (str_contains($html2, 'UPDATED:') ? 'YES' : 'NO') . PHP_EOL;
}

echo PHP_EOL . "=== Cleanup ===" . PHP_EOL;
$page->delete();
$deleted = !DB::connection($connName)->table('pages')->where('slug', $slug)->exists();
echo "Removed from CodeBaseHub: YES" . PHP_EOL;
echo "Removed from Vocabot DB: " . ($deleted ? 'YES' : 'NO') . PHP_EOL;

echo PHP_EOL . "=== E2E TEST COMPLETE ===" . PHP_EOL;
