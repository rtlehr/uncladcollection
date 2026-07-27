<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('homepage_discovery_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('section_key')->unique();
            $table->string('label');
            $table->string('eyebrow')->nullable();
            $table->string('heading')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('item_limit')->default(6);
            $table->boolean('is_enabled')->default(true);
            $table->string('audience', 24)->default('all');
            $table->timestamps();
        });

        $now = now();
        DB::table('homepage_discovery_sections')->insert([
            ['section_key' => 'primary_collections', 'label' => 'Primary featured collections', 'eyebrow' => 'Featured collection', 'heading' => 'Explore a curated collection', 'description' => 'Prominent featured or seasonal collection placements.', 'sort_order' => 10, 'item_limit' => 2, 'is_enabled' => true, 'audience' => 'all', 'created_at' => $now, 'updated_at' => $now],
            ['section_key' => 'recommended', 'label' => 'Recommended for you', 'eyebrow' => 'Selected for you', 'heading' => 'Recommended for you', 'description' => 'A personalized mix based on the assets and subjects a member explores.', 'sort_order' => 20, 'item_limit' => 6, 'is_enabled' => true, 'audience' => 'authenticated', 'created_at' => $now, 'updated_at' => $now],
            ['section_key' => 'trending', 'label' => 'Trending assets', 'eyebrow' => 'Popular this week', 'heading' => 'Trending in the marketplace', 'description' => 'Assets gaining attention through recent marketplace activity.', 'sort_order' => 30, 'item_limit' => 6, 'is_enabled' => true, 'audience' => 'all', 'created_at' => $now, 'updated_at' => $now],
            ['section_key' => 'featured_assets', 'label' => 'Featured assets', 'eyebrow' => 'Editor picks', 'heading' => 'Featured marketplace assets', 'description' => 'Hand-selected assets from the marketplace.', 'sort_order' => 40, 'item_limit' => 6, 'is_enabled' => true, 'audience' => 'all', 'created_at' => $now, 'updated_at' => $now],
            ['section_key' => 'secondary_collections', 'label' => 'Secondary featured collections', 'eyebrow' => 'Explore more', 'heading' => 'Featured and seasonal collections', 'description' => 'Timely, hand-selected collections for faster discovery.', 'sort_order' => 50, 'item_limit' => 6, 'is_enabled' => true, 'audience' => 'all', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_discovery_sections');
    }
};
