<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analytics_events', function (Blueprint $table): void {
            $table->char('fingerprint', 64)->nullable()->after('event_uuid');
            $table->index(['fingerprint', 'occurred_at'], 'analytics_fingerprint_period_idx');
            $table->index(['event_name', 'user_id', 'occurred_at'], 'analytics_event_user_period_idx');
            $table->index(['event_name', 'subject_type', 'subject_id', 'occurred_at'], 'analytics_event_subject_period_idx');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->index(['status', 'paid_at'], 'orders_status_paid_idx');
            $table->index(['user_id', 'status', 'paid_at'], 'orders_user_status_paid_idx');
            $table->index(['fulfillment_status', 'paid_at'], 'orders_fulfillment_paid_idx');
        });

        Schema::table('licenses', function (Blueprint $table): void {
            $table->index(['user_id', 'status', 'expires_at'], 'licenses_user_status_expiry_idx');
        });

        Schema::table('cart_items', function (Blueprint $table): void {
            $table->index(['user_id', 'updated_at'], 'cart_user_updated_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', fn (Blueprint $table) => $table->dropIndex('cart_user_updated_idx'));
        Schema::table('licenses', fn (Blueprint $table) => $table->dropIndex('licenses_user_status_expiry_idx'));
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_status_paid_idx');
            $table->dropIndex('orders_user_status_paid_idx');
            $table->dropIndex('orders_fulfillment_paid_idx');
        });
        Schema::table('analytics_events', function (Blueprint $table): void {
            $table->dropIndex('analytics_fingerprint_period_idx');
            $table->dropIndex('analytics_event_user_period_idx');
            $table->dropIndex('analytics_event_subject_period_idx');
            $table->dropColumn('fingerprint');
        });
    }
};
