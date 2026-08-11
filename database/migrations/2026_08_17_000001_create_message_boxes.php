<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('message_boxes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('title')->nullable();
            $table->longText('body_html')->nullable();
            $table->string('image_path')->nullable();
            $table->enum('presentation', ['modal', 'bottom_banner', 'top_banner'])->default('modal');
            $table->enum('trigger_type', ['auto', 'action'])->default('auto');
            $table->string('trigger_key')->nullable()->index();
            $table->json('page_patterns')->nullable();
            $table->enum('audience', ['all', 'guests', 'authenticated'])->default('all');
            $table->boolean('show_once')->default(false);
            $table->boolean('is_dismissible')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(100);
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->json('buttons')->nullable();
            $table->json('form_fields')->nullable();
            $table->string('form_submit_label')->nullable();
            $table->string('form_success_message')->nullable();
            $table->timestamps();
        });

        Schema::create('message_box_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_box_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('visitor_token')->nullable()->index();
            $table->timestamp('seen_at');
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();
            $table->index(['message_box_id', 'user_id']);
            $table->index(['message_box_id', 'visitor_token']);
        });

        Schema::create('message_box_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_box_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('visitor_token')->nullable()->index();
            $table->json('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_box_submissions');
        Schema::dropIfExists('message_box_views');
        Schema::dropIfExists('message_boxes');
    }
};
