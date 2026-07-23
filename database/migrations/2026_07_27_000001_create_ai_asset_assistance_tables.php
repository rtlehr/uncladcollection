<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->string('alt_text')->nullable()->after('description');
            $table->string('seo_title')->nullable()->after('alt_text');
            $table->text('seo_description')->nullable()->after('seo_title');
            $table->json('keywords')->nullable()->after('seo_description');
            $table->json('dominant_colors')->nullable()->after('keywords');
            $table->json('detected_objects')->nullable()->after('dominant_colors');
        });

        Schema::create('ai_asset_suggestions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('provider', 40)->default('openai');
            $table->string('model', 120)->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->string('source_type', 40)->nullable();
            $table->string('source_reference')->nullable();
            $table->json('suggestions')->nullable();
            $table->json('local_analysis')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->unsignedInteger('estimated_cost_micros')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'created_at'], 'ai_asset_suggestion_asset_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_asset_suggestions');

        Schema::table('assets', function (Blueprint $table): void {
            $table->dropColumn([
                'alt_text', 'seo_title', 'seo_description', 'keywords',
                'dominant_colors', 'detected_objects',
            ]);
        });
    }
};
