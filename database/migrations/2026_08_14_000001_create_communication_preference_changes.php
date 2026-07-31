<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_preference_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 80);
            $table->string('channel', 20);
            $table->boolean('old_value');
            $table->boolean('new_value');
            $table->string('source', 40)->default('account');
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();
            $table->index(['user_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_preference_changes');
    }
};
