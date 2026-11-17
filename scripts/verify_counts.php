<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\Page;
use App\Models\PageSection;

$masterCount = Page::whereNull('website_id')->count();
$websiteId = 1; // as per user note
$siteCount = Page::where('website_id', $websiteId)->count();
$masterSections = PageSection::whereIn('page_id', Page::whereNull('website_id')->pluck('id'))->count();
$siteSections = PageSection::whereIn('page_id', Page::where('website_id', $websiteId)->pluck('id'))->count();

echo "Master pages: $masterCount\n";
echo "Website $websiteId pages: $siteCount\n";
echo "Master sections: $masterSections\n";
echo "Website $websiteId sections: $siteSections\n";
?>