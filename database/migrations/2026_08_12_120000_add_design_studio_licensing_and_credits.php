<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('studio_credit_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('credits');
            $table->unsignedInteger('price_cents');
            $table->char('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('design_project_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('design_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_file_id')->nullable()->constrained('asset_files')->nullOnDelete();
            $table->timestamps();
            $table->unique(['design_project_id', 'license_id', 'asset_id', 'asset_file_id'], 'design_project_asset_unique');
            $table->index(['design_project_id', 'asset_id']);
        });

        Schema::create('studio_credit_transactions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('design_export_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('license_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('studio_credit_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->string('status', 20)->default('pending');
            $table->integer('credits');
            $table->unsignedInteger('amount_cents')->nullable();
            $table->char('currency', 3)->default('USD');
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'type']);
        });

        Schema::table('design_exports', function (Blueprint $table): void {
            $table->uuid('request_token')->nullable()->unique()->after('uuid');
            $table->foreignId('studio_credit_transaction_id')->nullable()->after('user_id')->constrained('studio_credit_transactions')->nullOnDelete();
            $table->unsignedInteger('studio_price_cents')->nullable()->after('studio_credit_transaction_id');
            $table->string('studio_billing_type', 30)->nullable()->after('studio_price_cents');
            $table->json('studio_billing_snapshot')->nullable()->after('studio_billing_type');
        });
    }

    public function down(): void
    {
        Schema::table('design_exports', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('studio_credit_transaction_id');
            $table->dropUnique(['request_token']);
            $table->dropColumn(['request_token', 'studio_price_cents', 'studio_billing_type', 'studio_billing_snapshot']);
        });

        Schema::dropIfExists('studio_credit_transactions');
        Schema::dropIfExists('design_project_assets');
        Schema::dropIfExists('studio_credit_packages');
    }
};
