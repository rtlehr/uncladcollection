<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('search_terms', function (Blueprint $table): void {
            $table->id();
            $table->string('normalized_term', 120)->unique();
            $table->string('display_term', 120);
            $table->unsignedBigInteger('search_count')->default(0);
            $table->unsignedBigInteger('unique_searchers')->default(0);
            $table->unsignedBigInteger('zero_result_count')->default(0);
            $table->decimal('average_results', 10, 2)->default(0);
            $table->unsignedBigInteger('click_count')->default(0);
            $table->unsignedBigInteger('favorite_count')->default(0);
            $table->unsignedBigInteger('cart_count')->default(0);
            $table->unsignedBigInteger('order_count')->default(0);
            $table->unsignedBigInteger('revenue_cents')->default(0);
            $table->timestamp('first_searched_at')->nullable();
            $table->timestamp('last_searched_at')->nullable();
            $table->boolean('is_content_opportunity')->default(false);
            $table->timestamps();
            $table->index(['zero_result_count', 'search_count']);
            $table->index('last_searched_at');
        });

        Schema::create('search_term_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('search_term_id')->constrained()->cascadeOnDelete();
            $table->string('raw_term', 120);
            $table->string('normalized_raw_term', 120);
            $table->unsignedBigInteger('search_count')->default(0);
            $table->timestamp('last_searched_at')->nullable();
            $table->timestamps();
            $table->unique(['search_term_id', 'normalized_raw_term'], 'search_term_variant_unique');
        });

        Schema::create('search_term_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('search_term_id')->constrained()->cascadeOnDelete();
            $table->string('suggested_canonical_term', 120)->nullable();
            $table->string('approved_canonical_term', 120)->nullable();
            $table->json('suggested_synonyms')->nullable();
            $table->json('approved_synonyms')->nullable();
            $table->string('intent_category', 80)->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->text('ai_explanation')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('source', 20)->default('ai');
            $table->string('provider', 50)->nullable();
            $table->string('model', 100)->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
            $table->unique('search_term_id');
            $table->index(['status', 'confidence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_term_mappings');
        Schema::dropIfExists('search_term_variants');
        Schema::dropIfExists('search_terms');
    }
};
