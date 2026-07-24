<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->string('cover_original_path')->nullable()->after('description');
            $table->string('cover_image_path')->nullable()->after('cover_original_path');
            $table->json('cover_edit_data')->nullable()->after('cover_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn([
                'cover_original_path',
                'cover_image_path',
                'cover_edit_data',
            ]);
        });
    }
};
