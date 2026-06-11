<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->unsignedInteger('downloads_count')->default(0)->after('sort_order');
            $table->unsignedInteger('favorites_count')->default(0)->after('downloads_count');
            $table->unsignedInteger('purchases_count')->default(0)->after('favorites_count');
            $table->unsignedInteger('views_count')->default(0)->after('purchases_count');

            $table->boolean('is_ai_generated')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn([
                'downloads_count',
                'favorites_count',
                'purchases_count',
                'views_count',
                'is_ai_generated',
            ]);
        });
    }
};