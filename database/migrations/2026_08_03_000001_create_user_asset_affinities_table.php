<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_asset_affinities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('dimension', 40);
            $table->string('value', 191);
            $table->decimal('score', 12, 4)->default(0);
            $table->unsignedInteger('signal_count')->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'dimension', 'value'], 'user_asset_affinity_unique');
            $table->index(['user_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_asset_affinities');
    }
};
