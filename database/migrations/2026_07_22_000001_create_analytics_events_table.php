<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->string('event_name', 80)->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->string('source', 60)->nullable()->index();
            $table->string('channel', 60)->nullable()->index();
            $table->string('currency', 3)->nullable();
            $table->bigInteger('value_cents')->nullable();
            $table->json('dimensions')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id', 'occurred_at'], 'analytics_subject_period_index');
            $table->index(['event_name', 'occurred_at'], 'analytics_event_period_index');
            $table->index(['user_id', 'occurred_at'], 'analytics_user_period_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
