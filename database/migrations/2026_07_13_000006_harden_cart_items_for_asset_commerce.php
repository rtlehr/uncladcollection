<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('cart_items', 'asset_id')) $table->foreignId('asset_id')->nullable()->after('image_id')->constrained()->nullOnDelete();
            if (! Schema::hasColumn('cart_items', 'asset_offering_id')) $table->foreignId('asset_offering_id')->nullable()->after('license_type_id')->constrained()->nullOnDelete();
            if (! Schema::hasColumn('cart_items', 'quantity')) $table->unsignedInteger('quantity')->default(1);
            if (! Schema::hasColumn('cart_items', 'configuration_hash')) $table->string('configuration_hash', 64)->nullable();
            if (! Schema::hasColumn('cart_items', 'configuration_snapshot')) $table->json('configuration_snapshot')->nullable();
            if (! Schema::hasColumn('cart_items', 'base_unit_price_cents')) $table->unsignedInteger('base_unit_price_cents')->nullable();
            if (! Schema::hasColumn('cart_items', 'configuration_adjustment_cents')) $table->integer('configuration_adjustment_cents')->default(0);
            if (! Schema::hasColumn('cart_items', 'final_unit_price_cents')) $table->unsignedInteger('final_unit_price_cents')->nullable();
            if (! Schema::hasColumn('cart_items', 'line_total_cents')) $table->unsignedInteger('line_total_cents')->nullable();
            if (! Schema::hasColumn('cart_items', 'pricing_snapshot')) $table->json('pricing_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        // Compatibility migration: columns are retained because later commerce packages depend on them.
    }
};
