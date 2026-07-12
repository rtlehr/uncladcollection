<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_type_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents');
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('download_limit')->nullable();
            $table->unsignedInteger('expires_after_days')->nullable();
            $table->boolean('include_all_active_files')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['asset_id', 'license_type_id'], 'asset_offerings_asset_license_unique');
            $table->index(['asset_id', 'is_active', 'sort_order'], 'asset_offerings_asset_sort_index');
        });

        Schema::create('asset_offering_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_offering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_file_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['asset_offering_id', 'asset_file_id'], 'asset_offering_files_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_offering_files');
        Schema::dropIfExists('asset_offerings');
    }
};
