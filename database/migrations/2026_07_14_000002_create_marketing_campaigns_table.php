<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('media_type', 20)->default('image');
            $table->string('media_path');
            $table->string('poster_path')->nullable();
            $table->string('headline')->nullable();
            $table->text('subheadline')->nullable();
            $table->string('eyebrow')->nullable();
            $table->string('primary_button_label')->nullable();
            $table->string('primary_button_url')->nullable();
            $table->string('secondary_button_label')->nullable();
            $table->string('secondary_button_url')->nullable();
            $table->unsignedTinyInteger('overlay_opacity')->default(35);
            $table->string('media_position', 20)->default('center');
            $table->string('hero_height', 20)->default('large');
            $table->string('text_alignment', 20)->default('left');
            $table->boolean('autoplay_first_visit')->default(true);
            $table->boolean('autoplay_mobile')->default(false);
            $table->boolean('loop_video')->default(true);
            $table->boolean('show_search')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at'], 'marketing_campaign_schedule_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_campaigns');
    }
};
