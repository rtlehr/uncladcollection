<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('text');
            $table->string('status', 30)->default('draft')->index();
            $table->string('media_disk', 40)->nullable();
            $table->string('media_path', 1000)->nullable();
            $table->string('media_type', 20)->nullable();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->string('x_post_id', 80)->nullable()->unique();
            $table->string('x_post_url', 1000)->nullable();
            $table->text('error_message')->nullable();
            $table->json('api_response')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
