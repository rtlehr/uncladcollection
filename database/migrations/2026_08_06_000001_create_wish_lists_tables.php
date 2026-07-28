<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wish_lists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name', 80);
            $table->string('slug', 100);
            $table->text('description')->nullable();
            $table->string('visibility', 20)->default('private');
            $table->boolean('is_default')->default(false);
            $table->string('share_token', 64)->nullable()->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
            $table->index(['user_id', 'is_default']);
            $table->index(['user_id', 'sort_order']);
        });

        Schema::create('wish_list_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wish_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('price_snapshot_cents')->nullable();
            $table->string('availability_snapshot', 30)->nullable();
            $table->timestamps();

            $table->unique(['wish_list_id', 'asset_id']);
            $table->index(['asset_id', 'created_at']);
        });

        $userIds = DB::table('asset_favorites')->pluck('user_id')
            ->merge(DB::table('image_favorites')->pluck('user_id'))
            ->unique()
            ->values();

        foreach ($userIds as $userId) {
            $createdAt = now();
            $wishListId = DB::table('wish_lists')->insertGetId([
                'user_id' => $userId,
                'uuid' => (string) Str::uuid(),
                'name' => 'Favorites',
                'slug' => 'favorites',
                'visibility' => 'private',
                'is_default' => true,
                'sort_order' => 0,
                'last_activity_at' => $createdAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $assetFavorites = DB::table('asset_favorites')
                ->where('user_id', $userId)
                ->orderBy('created_at')
                ->get(['asset_id', 'created_at', 'updated_at']);

            foreach ($assetFavorites as $favorite) {
                DB::table('wish_list_items')->insertOrIgnore([
                    'wish_list_id' => $wishListId,
                    'asset_id' => $favorite->asset_id,
                    'sort_order' => 0,
                    'created_at' => $favorite->created_at ?? $createdAt,
                    'updated_at' => $favorite->updated_at ?? $createdAt,
                ]);
            }

            $legacyFavorites = DB::table('image_favorites')
                ->join('assets', 'assets.legacy_image_id', '=', 'image_favorites.image_id')
                ->where('image_favorites.user_id', $userId)
                ->whereNull('assets.deleted_at')
                ->orderBy('image_favorites.created_at')
                ->get(['assets.id as asset_id', 'image_favorites.created_at', 'image_favorites.updated_at']);

            foreach ($legacyFavorites as $favorite) {
                DB::table('wish_list_items')->insertOrIgnore([
                    'wish_list_id' => $wishListId,
                    'asset_id' => $favorite->asset_id,
                    'sort_order' => 0,
                    'created_at' => $favorite->created_at ?? $createdAt,
                    'updated_at' => $favorite->updated_at ?? $createdAt,
                ]);

                DB::table('asset_favorites')->insertOrIgnore([
                    'user_id' => $userId,
                    'asset_id' => $favorite->asset_id,
                    'created_at' => $favorite->created_at ?? $createdAt,
                    'updated_at' => $favorite->updated_at ?? $createdAt,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wish_list_items');
        Schema::dropIfExists('wish_lists');
    }
};
