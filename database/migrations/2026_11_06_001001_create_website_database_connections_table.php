<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('website_database_connections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('website_id')->constrained('websites')->cascadeOnDelete();
            $table->string('driver');
            $table->string('host');
            $table->unsignedSmallInteger('port');
            $table->string('database');
            $table->string('username');
            $table->string('password'); // encrypted via model cast
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_database_connections');
    }
};
