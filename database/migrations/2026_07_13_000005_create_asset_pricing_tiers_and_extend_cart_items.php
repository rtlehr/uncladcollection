<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_pricing_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_offering_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('minimum_quantity');
            $table->unsignedInteger('maximum_quantity')->nullable();
            $table->string('pricing_type', 32)->default('fixed_unit_price');
            $table->unsignedInteger('unit_price_cents')->nullable();
            $table->decimal('percentage_off', 8, 4)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['asset_id', 'asset_offering_id', 'is_active', 'minimum_quantity'],
                'asset_pricing_tiers_lookup_idx'
            );
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('asset_id')->nullable()->after('image_id')->constrained()->nullOnDelete();
            $table->foreignId('asset_offering_id')->nullable()->after('license_type_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1)->after('asset_offering_id');
            $table->string('configuration_hash', 64)->nullable()->after('quantity')->index();
            $table->json('configuration_snapshot')->nullable()->after('configuration_hash');
            $table->unsignedInteger('base_unit_price_cents')->nullable()->after('price_cents');
            $table->integer('configuration_adjustment_cents')->default(0)->after('base_unit_price_cents');
            $table->unsignedInteger('final_unit_price_cents')->nullable()->after('configuration_adjustment_cents');
            $table->unsignedInteger('line_total_cents')->nullable()->after('final_unit_price_cents');
            $table->json('pricing_snapshot')->nullable()->after('line_total_cents');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asset_offering_id');
            $table->dropConstrainedForeignId('asset_id');
            $table->dropColumn([
                'quantity',
                'configuration_hash',
                'configuration_snapshot',
                'base_unit_price_cents',
                'configuration_adjustment_cents',
                'final_unit_price_cents',
                'line_total_cents',
                'pricing_snapshot',
            ]);
        });

        Schema::dropIfExists('asset_pricing_tiers');
    }
};
