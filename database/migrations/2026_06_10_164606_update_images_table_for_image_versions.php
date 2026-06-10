<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            if (Schema::hasColumn('images', 'image_path')) {
                $table->dropColumn('image_path');
            }

            $table->string('original_path')->nullable()->after('description');
            $table->string('high_res_path')->nullable()->after('original_path');
            $table->string('icon_path')->nullable()->after('high_res_path');

            if (! Schema::hasColumn('images', 'thumbnail_path')) {
                $table->string('thumbnail_path')->nullable()->after('icon_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn([
                'original_path',
                'high_res_path',
                'icon_path',
            ]);

            $table->string('image_path')->nullable()->after('description');
        });
    }
};