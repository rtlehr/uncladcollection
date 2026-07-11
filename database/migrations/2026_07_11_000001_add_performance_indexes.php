<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->index(
                ['is_active', 'created_at'],
                'images_active_created_index',
            );

            $table->index(
                ['is_active', 'collection_id', 'created_at'],
                'images_active_collection_created_index',
            );

            $table->index(
                ['is_active', 'is_ai_generated', 'created_at'],
                'images_active_ai_created_index',
            );

            $table->index(
                ['is_active', 'views_count'],
                'images_active_views_index',
            );

            $table->index(
                ['is_active', 'downloads_count'],
                'images_active_downloads_index',
            );
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->index(
                [
                    'status',
                    'is_active',
                    'published_at',
                    'expires_at',
                ],
                'blog_posts_publication_window_index',
            );

            $table->index(
                ['user_id', 'status', 'published_at'],
                'blog_posts_author_publication_index',
            );
        });

        Schema::table('downloads', function (Blueprint $table) {
            $table->index(
                'downloaded_at',
                'downloads_downloaded_at_index',
            );
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(
                'created_at',
                'orders_created_at_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropIndex('images_active_created_index');
            $table->dropIndex('images_active_collection_created_index');
            $table->dropIndex('images_active_ai_created_index');
            $table->dropIndex('images_active_views_index');
            $table->dropIndex('images_active_downloads_index');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropIndex('blog_posts_publication_window_index');
            $table->dropIndex('blog_posts_author_publication_index');
        });

        Schema::table('downloads', function (Blueprint $table) {
            $table->dropIndex('downloads_downloaded_at_index');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_created_at_index');
        });
    }
};
