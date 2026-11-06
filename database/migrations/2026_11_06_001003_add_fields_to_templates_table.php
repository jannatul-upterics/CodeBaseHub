<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add required fields to the templates table.
     */
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->string('view_path')->nullable()->after('slug');
            $table->string('image_path')->nullable()->after('preview_image');
        });
    }

    /**
     * Reverse the changes.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn(['view_path', 'image_path']);
        });
    }
};
