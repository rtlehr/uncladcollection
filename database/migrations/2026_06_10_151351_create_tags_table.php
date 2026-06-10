<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug');
            $table->enum('tag_type', ['image', 'blog'])->default('image');
            $table->text('description')->nullable();

            $table->timestamps();

            $table->unique(['slug', 'tag_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};