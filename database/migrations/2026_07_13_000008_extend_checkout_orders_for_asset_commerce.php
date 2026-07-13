<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('commerce_version', 16)->default('2.0')->after('order_number');
            $table->json('checkout_snapshot')->nullable()->after('metadata');
            $table->string('fulfillment_status', 32)->default('pending')->after('status');
            $table->timestamp('checkout_locked_at')->nullable()->after('canceled_at');

            $table->index(['status', 'fulfillment_status'], 'orders_status_fulfillment_idx');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('image_id')->nullable()->change();
            $table->string('asset_title')->nullable()->after('image_title');
            $table->string('offering_name')->nullable()->after('license_name');
            $table->string('configuration_hash', 64)->nullable()->after('offering_name');
            $table->json('configuration_snapshot')->nullable()->after('configuration_hash');
            $table->json('pricing_snapshot')->nullable()->after('configuration_snapshot');
            $table->string('fulfillment_type', 24)->default('digital')->after('status');
            $table->string('commerce_version', 16)->default('2.0')->after('fulfillment_type');
        });

        Schema::table('licenses', function (Blueprint $table): void {
            $table->unsignedBigInteger('image_id')->nullable()->change();
            $table->json('configuration_snapshot')->nullable()->after('included_asset_files_snapshot');
            $table->json('pricing_snapshot')->nullable()->after('configuration_snapshot');
            $table->string('fulfillment_type', 24)->default('digital')->after('status');
            $table->string('commerce_version', 16)->default('2.0')->after('fulfillment_type');
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table): void {
            $table->dropColumn([
                'configuration_snapshot',
                'pricing_snapshot',
                'fulfillment_type',
                'commerce_version',
            ]);
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn([
                'asset_title',
                'offering_name',
                'configuration_hash',
                'configuration_snapshot',
                'pricing_snapshot',
                'fulfillment_type',
                'commerce_version',
            ]);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_status_fulfillment_idx');
            $table->dropColumn([
                'commerce_version',
                'checkout_snapshot',
                'fulfillment_status',
                'checkout_locked_at',
            ]);
        });
    }
};
