<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations on tenant database.
     */
    public function up(): void
    {
        if (Schema::hasTable('pages')) {
            Schema::table('pages', function (Blueprint $table) {
                if (! Schema::hasColumn('pages', 'central_page_id')) {
                    $table->unsignedBigInteger('central_page_id')->nullable()->index()->after('id');
                }
                if (! Schema::hasColumn('pages', 'content')) {
                    $table->longText('content')->nullable()->after('status');
                }
                if (! Schema::hasColumn('pages', 'meta_title')) {
                    $table->string('meta_title')->nullable()->after('content');
                }
                if (! Schema::hasColumn('pages', 'meta_description')) {
                    $table->text('meta_description')->nullable()->after('meta_title');
                }
                if (! Schema::hasColumn('pages', 'canonical_url')) {
                    $table->string('canonical_url')->nullable()->after('meta_description');
                }
                if (! Schema::hasColumn('pages', 'og_image')) {
                    $table->string('og_image')->nullable()->after('canonical_url');
                }
                if (! Schema::hasColumn('pages', 'sections_data')) {
                    $table->json('sections_data')->nullable()->after('og_image');
                }
                if (! Schema::hasColumn('pages', 'sync_hash')) {
                    $table->string('sync_hash', 64)->nullable()->after('sections_data');
                }
            });
        }

        if (! Schema::hasTable('page_sections')) {
            Schema::create('page_sections', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('page_id')->index();
                $table->unsignedBigInteger('central_section_id')->nullable()->index();
                $table->string('section_name');
                $table->string('section_type');
                $table->json('content')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedTinyInteger('status')->default(1);
                $table->timestamps();

                $table->foreign('page_id')->references('id')->on('pages')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('metadata')) {
            Schema::create('metadata', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('page_id')->nullable()->index();
                $table->unsignedBigInteger('central_metadata_id')->nullable()->index();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('canonical_url')->nullable();
                $table->string('robots')->default('index, follow');
                $table->string('og_image')->nullable();
                $table->timestamps();

                $table->foreign('page_id')->references('id')->on('pages')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metadata');
        Schema::dropIfExists('page_sections');

        if (Schema::hasTable('pages')) {
            Schema::table('pages', function (Blueprint $table) {
                $columns = [];
                foreach (['central_page_id', 'content', 'meta_title', 'meta_description', 'canonical_url', 'og_image', 'sections_data', 'sync_hash'] as $col) {
                    if (Schema::hasColumn('pages', $col)) {
                        $columns[] = $col;
                    }
                }
                if (! empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
