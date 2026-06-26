<?php

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Comment::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(User::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('reason')->nullable();
            $table->text('details')->nullable();

            $table->string('status')->default('pending');

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['comment_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['reviewed_by', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_reports');
    }
};