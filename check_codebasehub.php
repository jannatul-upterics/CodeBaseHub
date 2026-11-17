<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Website;
use App\Services\PageSyncService;

echo "=== CodeBaseHub Websites ===\n";
$websites = \DB::table('websites')->get(['id', 'website_name', 'domain', 'provisioning_status'])->toArray();
echo json_encode($websites, JSON_PRETTY_PRINT)."\n";

echo "\n=== Pages in CodeBaseHub for Vocabot (website_id=6) ===\n";
$pages = \DB::table('pages')->where('website_id', 6)->get(['id','title','slug','status','is_homepage','tenant_page_id'])->toArray();
echo json_encode($pages, JSON_PRETTY_PRINT)."\n";

echo "\n=== Page Sections in CodeBaseHub for Vocabot ===\n";
$pageIds = array_column($pages, 'id');
if ($pageIds) {
    $sections = \DB::table('page_sections')->whereIn('page_id', $pageIds)->get(['id','page_id','section_name','section_type','status','content'])->toArray();
    echo json_encode($sections, JSON_PRETTY_PRINT)."\n";
} else {
    echo "No pages found, no sections to show.\n";
}

echo "\n=== Website DB Connection for Vocabot ===\n";
$conn = \DB::table('website_database_connections')->where('website_id', 6)->first();
if ($conn) {
    echo "DB: {$conn->database}, Host: {$conn->host}, Driver: {$conn->driver}\n";
} else {
    echo "No DB connection configured.\n";
}
