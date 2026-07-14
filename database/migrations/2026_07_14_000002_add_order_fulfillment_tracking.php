<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('shipping_carrier', 80)->nullable()->after('fulfillment_status');
            $table->string('tracking_number', 160)->nullable()->after('shipping_carrier');
            $table->text('fulfillment_notes')->nullable()->after('tracking_number');
            $table->timestamp('shipped_at')->nullable()->after('checkout_locked_at');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            $table->timestamp('fulfilled_at')->nullable()->after('delivered_at');
        });

        DB::table('orders')->where('fulfillment_status', 'pending')->update(['fulfillment_status' => 'new']);

        Schema::create('order_fulfillment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 40);
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['order_id','created_at'], 'order_fulfillment_timeline_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_fulfillment_events');
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['shipping_carrier','tracking_number','fulfillment_notes','shipped_at','delivered_at','fulfilled_at']);
        });
    }
};
