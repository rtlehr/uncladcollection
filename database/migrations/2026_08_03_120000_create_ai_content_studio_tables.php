<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_prompt_examples', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->text('content');
            $table->string('category')->nullable()->index();
            $table->string('content_context')->default('general')->index();
            $table->json('intended_uses')->nullable();
            $table->json('subject_tags')->nullable();
            $table->boolean('is_family_friendly')->default(true)->index();
            $table->boolean('is_enabled')->default(true)->index();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->string('source_filename')->nullable();
            $table->unsignedInteger('source_index')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->text('normalized_content')->nullable();
            $table->unique(['source_filename', 'source_index']);
        });

        Schema::create('ai_content_policies', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->longText('instructions');
            $table->string('applies_to')->default('all');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('feature')->index();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->string('status')->default('completed')->index();
            $table->longText('input_text');
            $table->json('input_context')->nullable();
            $table->longText('output_text')->nullable();
            $table->json('output_data')->nullable();
            $table->json('prompt_example_ids')->nullable();
            $table->json('policy_keys')->nullable();
            $table->string('prompt_template_version')->default('1');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
        Schema::dropIfExists('ai_content_policies');
        Schema::dropIfExists('ai_prompt_examples');
    }
};
