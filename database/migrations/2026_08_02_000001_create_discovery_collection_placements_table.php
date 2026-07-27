<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discovery_collection_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->string('placement', 50)->default('homepage_primary');
            $table->string('content_type', 20)->default('featured');
            $table->string('audience', 20)->default('all');
            $table->string('eyebrow')->nullable();
            $table->string('heading')->nullable();
            $table->text('description')->nullable();
            $table->string('call_to_action')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['placement', 'is_active', 'starts_at', 'ends_at'], 'discovery_collection_schedule_idx');
            $table->index(['audience', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discovery_collection_placements');
    }
};
