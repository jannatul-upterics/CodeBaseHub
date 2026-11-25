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
                $table->unsignedBigInteger('website_id')->nullable()->index();
                $table->unsignedBigInteger('central_page_id')->nullable()->index();
                $table->unsignedBigInteger('tenant_page_id')->nullable()->index();
                $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
                $table->unsignedBigInteger('template_id')->nullable();
                $table->string('template_slug')->nullable();
                $table->unsignedBigInteger('master_page_id')->nullable();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('page_type')->default('standard');
                $table->boolean('is_homepage')->default(false);
                $table->boolean('is_template')->default(false);
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
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
