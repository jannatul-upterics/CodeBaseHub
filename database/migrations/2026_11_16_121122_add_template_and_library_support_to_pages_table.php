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
            $table->unsignedBigInteger('website_id')->nullable()->change();

            if (! Schema::hasColumn('pages', 'is_template')) {
                $table->boolean('is_template')->default(false)->index()->after('is_homepage');
            }

            if (! Schema::hasColumn('pages', 'sections_data')) {
                $table->json('sections_data')->nullable()->after('og_image');
            }

            $table->longText('content')->nullable()->change();
        });

        if (Schema::hasTable('metadata') && Schema::hasColumn('metadata', 'website_id')) {
            Schema::table('metadata', function (Blueprint $table) {
                $table->unsignedBigInteger('website_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('pages', 'is_template')) {
                $columnsToDrop[] = 'is_template';
            }
            if (Schema::hasColumn('pages', 'sections_data')) {
                $columnsToDrop[] = 'sections_data';
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
