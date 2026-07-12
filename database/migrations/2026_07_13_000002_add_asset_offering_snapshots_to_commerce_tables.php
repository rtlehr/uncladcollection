<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('asset_id')->nullable()->after('image_id')->constrained()->nullOnDelete();
            $table->foreignId('asset_offering_id')->nullable()->after('license_type_id')->constrained()->nullOnDelete();
            $table->json('included_asset_files_snapshot')->nullable()->after('license_terms');
        });

        Schema::table('licenses', function (Blueprint $table) {
            $table->foreignId('asset_id')->nullable()->after('image_id')->constrained()->nullOnDelete();
            $table->foreignId('asset_offering_id')->nullable()->after('license_type_id')->constrained()->nullOnDelete();
            $table->json('included_asset_files_snapshot')->nullable()->after('license_terms');
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asset_offering_id');
            $table->dropConstrainedForeignId('asset_id');
            $table->dropColumn('included_asset_files_snapshot');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asset_offering_id');
            $table->dropConstrainedForeignId('asset_id');
            $table->dropColumn('included_asset_files_snapshot');
        });
    }
};
