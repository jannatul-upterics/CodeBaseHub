<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('page_type');
                $table->boolean('is_homepage')->default(false);
                $table->unsignedTinyInteger('status')->default(1);
                // optional parent for nesting
                $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
                // optional reference to central template (store slug)
                $table->string('template_slug')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_indexable')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
