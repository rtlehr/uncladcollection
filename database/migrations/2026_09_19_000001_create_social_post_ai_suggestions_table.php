<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_post_ai_suggestions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ai_generation_id')->constrained('ai_generations')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('subject');
            $table->unsignedSmallInteger('position')->default(1);
            $table->text('text');
            $table->json('hashtags')->nullable();
            $table->text('image_suggestion')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('social_post_id')->nullable()->constrained('social_posts')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(['ai_generation_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_post_ai_suggestions');
    }
};
