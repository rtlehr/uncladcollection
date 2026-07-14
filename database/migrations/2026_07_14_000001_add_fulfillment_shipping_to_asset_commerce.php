<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->string('fulfillment_type', 24)->default('digital')->after('allows_quantity');
            $table->boolean('collects_shipping_address')->default(false)->after('fulfillment_type');
            $table->boolean('shipping_address_required')->default(false)->after('collects_shipping_address');
        });

        Schema::table('cart_items', function (Blueprint $table): void {
            $table->string('shipping_address_hash', 64)->nullable()->after('configuration_hash');
            $table->json('shipping_address_snapshot')->nullable()->after('configuration_snapshot');
            $table->index(['user_id', 'asset_offering_id', 'shipping_address_hash'], 'cart_shipping_merge_idx');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->json('shipping_address_snapshot')->nullable()->after('configuration_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('shipping_address_snapshot'));
        Schema::table('cart_items', function (Blueprint $table): void {
            $table->dropIndex('cart_shipping_merge_idx');
            $table->dropColumn(['shipping_address_hash', 'shipping_address_snapshot']);
        });
        Schema::table('assets', fn (Blueprint $table) => $table->dropColumn([
            'fulfillment_type', 'collects_shipping_address', 'shipping_address_required',
        ]));
    }
};
