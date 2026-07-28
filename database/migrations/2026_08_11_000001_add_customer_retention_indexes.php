<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wish_list_items', function (Blueprint $table): void {
            $table->index(['asset_id', 'created_at'], 'wish_list_items_asset_created_idx');
        });
        Schema::table('notification_watch_events', function (Blueprint $table): void {
            $table->index('created_at', 'notification_watch_events_created_idx');
        });
        Schema::table('downloads', function (Blueprint $table): void {
            $table->index(['user_id', 'license_id', 'downloaded_at'], 'downloads_retention_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('wish_list_items', fn (Blueprint $table) => $table->dropIndex('wish_list_items_asset_created_idx'));
        Schema::table('notification_watch_events', fn (Blueprint $table) => $table->dropIndex('notification_watch_events_created_idx'));
        Schema::table('downloads', fn (Blueprint $table) => $table->dropIndex('downloads_retention_lookup_idx'));
    }
};
