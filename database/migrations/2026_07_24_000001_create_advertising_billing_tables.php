<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('advertising_invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invoice_number')->unique();
            $table->foreignId('advertiser_id')->constrained()->cascadeOnDelete();
            $table->foreignId('advertising_campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('draft')->index();
            $table->string('currency', 3)->default('USD');
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('discount_cents')->default(0);
            $table->unsignedBigInteger('tax_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->unsignedBigInteger('paid_cents')->default(0);
            $table->unsignedBigInteger('refunded_cents')->default(0);
            $table->unsignedBigInteger('balance_cents')->default(0);
            $table->date('issued_at')->nullable()->index();
            $table->date('due_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['advertiser_id', 'status']);
            $table->index(['status', 'due_at']);
        });

        Schema::create('advertising_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advertising_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->string('billing_model')->default('flat');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_amount_cents')->default(0);
            $table->unsignedBigInteger('line_total_cents')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('advertising_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('advertising_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('payment')->index();
            $table->string('status')->default('pending')->index();
            $table->string('provider')->default('manual');
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency', 3)->default('USD');
            $table->string('provider_reference')->nullable()->index();
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['advertising_invoice_id', 'status']);
        });

        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->change();
            $table->foreignId('advertising_invoice_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
            $table->foreignId('advertising_payment_id')->nullable()->after('advertising_invoice_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('advertising_payment_id');
            $table->dropConstrainedForeignId('advertising_invoice_id');
        });
        Schema::dropIfExists('advertising_payments');
        Schema::dropIfExists('advertising_invoice_items');
        Schema::dropIfExists('advertising_invoices');
    }
};
