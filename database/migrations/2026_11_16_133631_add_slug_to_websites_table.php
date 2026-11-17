<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add slug column to existing websites table
        Schema::table('websites', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable();
        });

        // Backfill slug for existing records
        DB::table('websites')->orderBy('id')->chunk(100, function ($websites) {
            foreach ($websites as $website) {
                $base = Str::slug($website->website_name);
                $slug = $base;
                $suffix = 1;
                // Ensure uniqueness
                while (DB::table('websites')->where('slug', $slug)->where('id', '!=', $website->id)->exists()) {
                    $slug = $base . '-' . $suffix++;
                }
                DB::table('websites')->where('id', $website->id)->update(['slug' => $slug]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
