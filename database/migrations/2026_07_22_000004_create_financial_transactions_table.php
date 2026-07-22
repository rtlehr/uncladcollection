<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('fulfillment_status', 32)->default('new')->change();
        });

        Schema::create('financial_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Order::class)->constrained()->cascadeOnDelete();
            $table->string('type', 24);
            $table->string('status', 24)->default('succeeded');
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('USD');
            $table->string('provider', 32)->nullable();
            $table->string('provider_reference')->nullable();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->unique(['provider', 'provider_reference'], 'financial_provider_reference_unique');
            $table->index(['type', 'status', 'occurred_at'], 'financial_type_status_date_idx');
            $table->index(['order_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('fulfillment_status', 32)->default('pending')->change();
        });
    }
};
