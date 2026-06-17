<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->string('header_image_path')->nullable()->after('featured_image_path');
            $table->string('icon_image_path')->nullable()->after('header_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn([
                'header_image_path',
                'icon_image_path',
            ]);
        });
    }
};