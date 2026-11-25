<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('website_id')->nullable()->constrained('websites')->cascadeOnDelete();
            $table->unsignedBigInteger('tenant_page_id')->nullable()->index();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->foreignId('master_page_id')->nullable()->constrained('pages')->cascadeOnUpdate()->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('page_type');
            $table->boolean('is_homepage')->default(false);
            $table->boolean('is_template')->default(false)->index();
            $table->unsignedTinyInteger('status')->default(1);
            $table->longText('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_image')->nullable();
            $table->json('sections_data')->nullable();
            $table->string('sync_hash', 64)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_indexable')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['website_id', 'master_page_id'], 'uq_website_master_page');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
