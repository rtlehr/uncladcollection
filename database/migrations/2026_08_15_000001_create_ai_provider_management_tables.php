<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('driver'); // ollama, openai, venice
            $table->string('base_url');
            $table->text('api_key')->nullable();
            $table->string('default_model')->nullable();
            $table->unsignedInteger('connect_timeout_seconds')->default(20);
            $table->unsignedInteger('timeout_seconds')->default(300);
            $table->unsignedTinyInteger('retry_times')->default(2);
            $table->boolean('streaming_enabled')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status')->nullable();
            $table->text('last_test_message')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ai_feature_assignments', function (Blueprint $table): void {
            $table->id();
            $table->string('feature')->unique();
            $table->foreignId('primary_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->string('primary_model')->nullable();
            $table->foreignId('fallback_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->string('fallback_model')->nullable();
            $table->boolean('fallback_enabled')->default(false);
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_feature_assignments');
        Schema::dropIfExists('ai_providers');
    }
};
