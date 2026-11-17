<?php

namespace App\Console\Commands;

use App\Services\VocabotPageLibraryService;
use Illuminate\Console\Command;

class ImportVocabotPages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vocabot:import-pages {--force : Re-import even if already imported}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import and store all existing Vocabot pages and sections into the central CodeBaseHub database.';

    /**
     * Execute the console command.
     */
    public function handle(VocabotPageLibraryService $libraryService): int
    {
        $this->info('Starting import of Vocabot pages and sections into CodeBaseHub central database...');

        $result = $libraryService->importToCentralDatabase();

        $this->info("Successfully imported {$result['imported_pages']} pages with {$result['total_sections']} sections into CodeBaseHub!");

        $this->table(
            ['ID', 'Title', 'Slug', 'Type', 'Homepage', 'Sections'],
            collect($result['pages'])->map(function ($page) {
                return [
                    $page->id,
                    $page->title,
                    $page->slug,
                    $page->page_type,
                    $page->is_homepage ? 'Yes' : 'No',
                    $page->sections()->count(),
                ];
            })
        );

        return self::SUCCESS;
    }
}
