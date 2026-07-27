<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_category', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['asset_id', 'category_id']);
        });

        Schema::create('asset_favorites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'asset_id']);
            $table->index(['asset_id', 'created_at']);
        });

        DB::table('assets')
            ->whereNotNull('legacy_image_id')
            ->orderBy('id')
            ->chunkById(250, function ($assets): void {
                foreach ($assets as $asset) {
                    $now = now();
                    $categoryIds = DB::table('category_image')->where('image_id', $asset->legacy_image_id)->pluck('category_id');
                    foreach ($categoryIds as $categoryId) {
                        DB::table('asset_category')->updateOrInsert(
                            ['asset_id' => $asset->id, 'category_id' => $categoryId],
                            ['created_at' => $now, 'updated_at' => $now],
                        );
                    }

                    $tagIds = DB::table('image_tag')->where('image_id', $asset->legacy_image_id)->pluck('tag_id');
                    foreach ($tagIds as $tagId) {
                        DB::table('asset_tag')->updateOrInsert(
                            ['asset_id' => $asset->id, 'tag_id' => $tagId],
                            ['created_at' => $now, 'updated_at' => $now],
                        );
                    }

                    $favorites = DB::table('image_favorites')->where('image_id', $asset->legacy_image_id)->get(['user_id', 'created_at', 'updated_at']);
                    foreach ($favorites as $favorite) {
                        DB::table('asset_favorites')->updateOrInsert(
                            ['user_id' => $favorite->user_id, 'asset_id' => $asset->id],
                            ['created_at' => $favorite->created_at ?? $now, 'updated_at' => $favorite->updated_at ?? $now],
                        );
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_favorites');
        Schema::dropIfExists('asset_category');
    }
};
