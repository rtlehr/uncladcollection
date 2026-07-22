<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('license_types', function (Blueprint $table) {
            $table->unsignedInteger('image_unit_price_cents')->default(0)->after('price_cents');
            $table->unsignedInteger('video_unit_price_cents')->default(0)->after('image_unit_price_cents');
            $table->unsignedInteger('minimum_price_cents')->nullable()->after('video_unit_price_cents');
        });

        DB::table('license_types')->orderBy('id')->each(function ($license): void {
            DB::table('license_types')->where('id', $license->id)->update([
                'image_unit_price_cents' => (int) $license->price_cents,
                'video_unit_price_cents' => (int) $license->price_cents,
            ]);
        });

        Schema::table('asset_offerings', function (Blueprint $table) {
            $table->unsignedInteger('image_units')->default(1)->after('description');
            $table->unsignedInteger('video_units')->default(0)->after('image_units');
            $table->integer('price_adjustment_cents')->default(0)->after('price_cents');
            $table->unsignedInteger('price_override_cents')->nullable()->after('price_adjustment_cents');
        });
    }

    public function down(): void
    {
        Schema::table('asset_offerings', function (Blueprint $table) {
            $table->dropColumn(['image_units', 'video_units', 'price_adjustment_cents', 'price_override_cents']);
        });

        Schema::table('license_types', function (Blueprint $table) {
            $table->dropColumn(['image_unit_price_cents', 'video_unit_price_cents', 'minimum_price_cents']);
        });
    }
};
