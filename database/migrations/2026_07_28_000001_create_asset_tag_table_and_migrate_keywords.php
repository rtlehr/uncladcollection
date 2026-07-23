<?php

use App\Models\Asset;
use App\Models\Tag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_tag', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['asset_id', 'tag_id']);
        });

        Asset::query()
            ->select(['id', 'keywords'])
            ->whereNotNull('keywords')
            ->orderBy('id')
            ->chunkById(100, function ($assets): void {
                foreach ($assets as $asset) {
                    $keywords = is_array($asset->keywords)
                        ? $asset->keywords
                        : (json_decode((string) $asset->keywords, true) ?: []);

                    foreach ($keywords as $keyword) {
                        $name = trim((string) $keyword);
                        if ($name === '') {
                            continue;
                        }

                        $slug = Str::slug($name);
                        if ($slug === '') {
                            continue;
                        }

                        $tag = Tag::query()->firstOrCreate(
                            ['slug' => $slug, 'tag_type' => 'image'],
                            ['name' => $name, 'description' => null],
                        );

                        DB::table('asset_tag')->updateOrInsert(
                            ['asset_id' => $asset->id, 'tag_id' => $tag->id],
                            ['created_at' => now(), 'updated_at' => now()],
                        );
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_tag');
    }
};
