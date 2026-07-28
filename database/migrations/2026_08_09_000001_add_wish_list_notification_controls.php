<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wish_lists', function (Blueprint $table): void {
            $table->boolean('notify_price_changes')->default(false)->after('share_token');
            $table->boolean('notify_availability_changes')->default(true)->after('notify_price_changes');
            $table->boolean('notify_collection_changes')->default(false)->after('notify_availability_changes');
        });

        Schema::create('notification_watch_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('wish_list_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 80);
            $table->string('fingerprint', 64);
            $table->json('context')->nullable();
            $table->timestamp('notified_at');
            $table->timestamps();
            $table->unique(['user_id', 'event_type', 'fingerprint'], 'notification_watch_unique');
            $table->index(['event_type', 'notified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_watch_events');
        Schema::table('wish_lists', function (Blueprint $table): void {
            $table->dropColumn(['notify_price_changes', 'notify_availability_changes', 'notify_collection_changes']);
        });
    }
};
