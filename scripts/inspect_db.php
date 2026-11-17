<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CODEBASEHUB WEBSITES ===\n";
$websites = DB::table('websites')->get();
foreach ($websites as $w) {
    echo "ID: {$w->id} | Name: {$w->website_name} | Domain: {$w->domain} | Slug: " . ($w->slug ?? 'N/A') . "\n";
}

echo "\n=== ALL PAGES IN CODEBASEHUB ===\n";
$pages = DB::table('pages')->get();
foreach ($pages as $p) {
    echo "ID: {$p->id} | website_id: {$p->website_id} | slug: {$p->slug} | title: {$p->title} | is_home: {$p->is_homepage} | status: {$p->status}\n";
}

echo "\n=== ALL PAGES IN VOCABOT DATABASE ===\n";
try {
$website = \App\Models\Website::find(1);
echo "Website: {$website->website_name} (ID: {$website->id})\n";
$service = app(\App\Services\VocabotPageLibraryService::class);
$result = $service->importToCentralDatabase($website);
echo "Import result: {$result['imported_pages']} pages, {$result['total_sections']} sections.\n";

$pages = DB::table('pages')->where('website_id', 1)->get();
foreach ($pages as $p) {
    echo "ID: {$p->id} | slug: {$p->slug} | title: {$p->title} | is_home: {$p->is_homepage} | status: {$p->status} | sections count: " . DB::table('page_sections')->where('page_id', $p->id)->count() . "\n";
}



} catch (Exception $e) {
    echo "Error querying vocabot database: " . $e->getMessage() . "\n";
}





