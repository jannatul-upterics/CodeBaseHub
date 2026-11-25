<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('templates')->nullOnDelete();
            $table->string('website_name');
            $table->string('slug')->unique()->nullable();
            $table->string('domain');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->string('provider');
            $table->string('provisioning_status')
                ->default('pending')
                ->comment('pending|provisioning|provisioned|failed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('websites');
    }
};
