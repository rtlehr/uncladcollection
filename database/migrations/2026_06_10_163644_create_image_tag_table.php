<?php

use App\Models\Image;
use App\Models\Tag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_tag', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Image::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(Tag::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['image_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_tag');
    }
};