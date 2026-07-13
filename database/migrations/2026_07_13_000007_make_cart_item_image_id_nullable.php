<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cart_items') || ! Schema::hasColumn('cart_items', 'image_id')) {
            return;
        }

        // Native asset cart lines may not have a legacy images record.
        // MySQL retains the existing foreign key while the column is changed
        // from NOT NULL to NULL.
        DB::statement('ALTER TABLE `cart_items` MODIFY `image_id` BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('cart_items') || ! Schema::hasColumn('cart_items', 'image_id')) {
            return;
        }

        if (DB::table('cart_items')->whereNull('image_id')->exists()) {
            throw new RuntimeException(
                'Cannot make cart_items.image_id required while native asset cart lines exist.'
            );
        }

        DB::statement('ALTER TABLE `cart_items` MODIFY `image_id` BIGINT UNSIGNED NOT NULL');
    }
};
