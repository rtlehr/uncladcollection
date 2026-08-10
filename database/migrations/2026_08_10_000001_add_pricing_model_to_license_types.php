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
            $table->string('pricing_model', 32)->default('per_unit')->after('description');
            $table->unsignedInteger('total_price_cents')->nullable()->after('video_unit_price_cents');
        });

        DB::table('license_types')->orderBy('id')->each(function ($license): void {
            DB::table('license_types')->where('id', $license->id)->update([
                'pricing_model' => 'per_unit',
                'total_price_cents' => null,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('license_types', function (Blueprint $table) {
            $table->dropColumn(['pricing_model', 'total_price_cents']);
        });
    }
};
