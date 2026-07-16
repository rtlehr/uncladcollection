<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->string('header_image_original_path')->nullable()
                ->after('header_image_path');
            $table->string('icon_image_original_path')->nullable()
                ->after('icon_image_path');
            $table->json('image_edit_data')->nullable()
                ->after('icon_image_original_path');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->dropColumn([
                'header_image_original_path',
                'icon_image_original_path',
                'image_edit_data',
            ]);
        });
    }
};
