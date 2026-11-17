<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // Nullable self‑reference to a master definition
            $table->foreignId('master_page_id')
                  ->nullable()
                  ->constrained('pages')
                  ->cascadeOnUpdate()
                  ->nullOnDelete();

            // Unique combination: a website can have at most one derived page per master
            $table->unique(['website_id', 'master_page_id'], 'uq_website_master_page');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique('uq_website_master_page');
            $table->dropForeign(['master_page_id']);
            $table->dropColumn('master_page_id');
        });
    }
};