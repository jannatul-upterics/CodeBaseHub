<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('metadata')) {
            Schema::create('metadata', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('website_id')->nullable()->index();
                $table->foreignId('page_id')->nullable()->constrained('pages')->cascadeOnDelete();
                $table->unsignedBigInteger('central_metadata_id')->nullable()->index();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('canonical_url')->nullable();
                $table->string('robots')->default('index, follow');
                $table->string('og_image')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('metadata');
    }
};
