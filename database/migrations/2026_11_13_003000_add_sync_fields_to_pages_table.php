<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            if (! Schema::hasColumn('pages', 'tenant_page_id')) {
                $table->unsignedBigInteger('tenant_page_id')->nullable()->index()->after('website_id');
            }
            if (! Schema::hasColumn('pages', 'canonical_url')) {
                $table->string('canonical_url')->nullable()->after('meta_description');
            }
            if (! Schema::hasColumn('pages', 'og_image')) {
                $table->string('og_image')->nullable()->after('canonical_url');
            }
            if (! Schema::hasColumn('pages', 'sync_hash')) {
                $table->string('sync_hash', 64)->nullable()->after('og_image');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $columns = [];
            foreach (['tenant_page_id', 'canonical_url', 'og_image', 'sync_hash'] as $col) {
                if (Schema::hasColumn('pages', $col)) {
                    $columns[] = $col;
                }
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
