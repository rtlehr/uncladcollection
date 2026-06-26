<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->boolean('comments_enabled')->default(true)->after('is_active');
            $table->boolean('comments_visible')->default(true)->after('comments_enabled');
            $table->boolean('comments_require_approval')->default(false)->after('comments_visible');

            $table->index(['comments_enabled', 'comments_visible']);
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropIndex(['comments_enabled', 'comments_visible']);

            $table->dropColumn([
                'comments_enabled',
                'comments_visible',
                'comments_require_approval',
            ]);
        });
    }
};