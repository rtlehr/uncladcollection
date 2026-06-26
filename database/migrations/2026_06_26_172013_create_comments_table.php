<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            $table->morphs('commentable');

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('comments')
                ->nullOnDelete();

            $table->text('body');

            $table->string('status')->default('approved');

            $table->unsignedTinyInteger('depth')->default(0);

            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('reports_count')->default(0);

            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_edited')->default(false);

            $table->timestamp('edited_at')->nullable();
            $table->timestamp('hidden_at')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['commentable_type', 'commentable_id', 'status']);
            $table->index(['parent_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['is_pinned', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};