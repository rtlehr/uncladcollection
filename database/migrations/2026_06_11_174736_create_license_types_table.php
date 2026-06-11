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
        Schema::create('license_types', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            $table->string('currency', 3)->default('USD');

            $table->unsignedInteger('download_limit')->nullable();
            $table->unsignedInteger('expires_after_days')->nullable();

            $table->string('max_resolution')->default('high_res');

            $table->longText('usage_terms')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_types');
    }
};