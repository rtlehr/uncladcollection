<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_ticket_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 40)->default('status_change')->index();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40)->nullable();
            $table->string('from_priority', 30)->nullable();
            $table->string('to_priority', 30)->nullable();
            $table->foreignId('from_assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('public_note')->nullable();
            $table->text('internal_note')->nullable();
            $table->timestamps();
            $table->index(['support_ticket_id', 'created_at'], 'st_history_ticket_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_status_histories');
    }
};
