<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('ai_saved_prompts')) {
            Schema::create('ai_saved_prompts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('title');
                $table->text('description')->nullable();
                $table->longText('prompt_text');
                $table->string('intended_use', 50)->default('general_image');
                $table->string('content_context', 50)->default('general');
                $table->string('output_mode', 50)->default('content_only');
                $table->string('body_detail_level', 50)->default('contextual');
                $table->string('description_depth', 50)->default('expanded');
                $table->string('character_detail_level', 50)->default('detailed');
                $table->string('environment_detail_level', 50)->default('detailed');
                $table->boolean('describe_every_visible_person')->default(true);
                $table->string('orientation', 30)->default('landscape');
                $table->text('additional_instructions')->nullable();
                $table->string('provider')->nullable();
                $table->string('model')->nullable();
                $table->unsignedBigInteger('source_generation_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->foreign('source_generation_id')
                    ->references('id')
                    ->on('ai_generations')
                    ->nullOnDelete();

                $table->foreign('created_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table->foreign('updated_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table->index(['title', 'updated_at']);
                $table->index(['content_context', 'intended_use']);
            });
        }

        if (! Schema::hasTable('ai_saved_prompt_versions')) {
            Schema::create('ai_saved_prompt_versions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ai_saved_prompt_id');
                $table->unsignedInteger('version_number');
                $table->longText('prompt_text');
                $table->text('refinement_instruction')->nullable();
                $table->string('provider')->nullable();
                $table->string('model')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('ai_saved_prompt_id')
                    ->references('id')
                    ->on('ai_saved_prompts')
                    ->cascadeOnDelete();

                $table->foreign('created_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table->unique(
                    ['ai_saved_prompt_id', 'version_number'],
                    'ai_saved_prompt_version_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_saved_prompt_versions');
        Schema::dropIfExists('ai_saved_prompts');
    }
};
