<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('page_sections')) {
            Schema::create('page_sections', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
                $table->unsignedBigInteger('central_section_id')->nullable()->index();
                $table->string('section_name');
                $table->string('section_type');
                $table->json('content')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedTinyInteger('status')->default(1);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('page_sections');
    }
};
