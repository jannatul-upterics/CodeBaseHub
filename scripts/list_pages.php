<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
// Override DB connection to SQLite for this script
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE='.__DIR__.'/../database/database.sqlite');
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\Page;
try {
    $count = Page::count();
    echo "Pages count: $count\n";
    // List page titles and slugs
    $pages = Page::all(['id','title','slug']);
    foreach ($pages as $p) {
        echo $p->id." | ".$p->title." | ".$p->slug."\n";
    }
} catch (Throwable $e) {
    echo "Error: ".$e->getMessage();
}
?>