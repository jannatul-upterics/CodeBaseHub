<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add appearance configuration to themes.
     */
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            // Store appearance data as JSON (colors, fonts, spacing, etc.)
            $table->json('appearance')->nullable()->after('status');
        });
    }

    /**
     * Reverse the changes.
     */
    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn('appearance');
        });
    }
};
