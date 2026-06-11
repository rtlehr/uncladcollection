<?php

use App\Models\Image;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_favorites', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(Image::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['user_id', 'image_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_favorites');
    }
};