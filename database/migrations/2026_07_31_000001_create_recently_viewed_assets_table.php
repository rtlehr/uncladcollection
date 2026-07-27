<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recently_viewed_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('source', 64)->nullable();
            $table->unsignedInteger('view_count')->default(1);
            $table->timestamp('last_viewed_at');
            $table->timestamps();

            $table->unique(['user_id', 'asset_id']);
            $table->index(['user_id', 'last_viewed_at']);
            $table->index(['asset_id', 'last_viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recently_viewed_assets');
    }
};
