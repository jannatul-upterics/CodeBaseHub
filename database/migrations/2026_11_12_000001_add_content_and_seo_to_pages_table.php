<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add content and SEO columns to pages table.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->text('content')->nullable()->after('status');
            $table->string('meta_title')->nullable()->after('content');
            $table->text('meta_description')->nullable()->after('meta_title');
        });
    }

    /**
     * Reverse the changes.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['content', 'meta_title', 'meta_description']);
        });
    }
};