<?php

use App\Models\Category;
use App\Models\Image;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_image', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Category::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(Image::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['category_id', 'image_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_image');
    }
};