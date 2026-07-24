<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('ticket_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable()->index();
            $table->string('guest_access_token_hash', 64)->nullable()->unique();
            $table->foreignId('category_id')->nullable()->constrained('support_ticket_categories')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('new')->index();
            $table->string('priority', 30)->default('normal')->index();
            $table->string('source', 30)->default('member')->index();
            $table->string('subject');
            $table->longText('description');
            $table->timestamp('last_customer_reply_at')->nullable()->index();
            $table->timestamp('last_staff_reply_at')->nullable()->index();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->longText('resolution_summary')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['assigned_user_id', 'status', 'priority']);
            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
