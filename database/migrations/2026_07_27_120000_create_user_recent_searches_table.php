<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_recent_searches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('term', 120);
            $table->string('normalized_term', 120);
            $table->timestamp('searched_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'normalized_term']);
            $table->index(['user_id', 'searched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_recent_searches');
    }
};
