<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();

            $table->string('group_name')->default('general');
            $table->string('setting_key');
            $table->longText('setting_value')->nullable();
            $table->string('setting_type')->default('text');

            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);

            $table->timestamps();

            $table->unique(['group_name', 'setting_key']);
            $table->index('group_name');
            $table->index('is_public');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};