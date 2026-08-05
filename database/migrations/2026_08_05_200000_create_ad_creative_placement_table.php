<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ad_creative_placement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_creative_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ad_placement_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['ad_creative_id', 'ad_placement_id'], 'ad_creative_placement_unique');
            $table->index(['ad_placement_id', 'ad_creative_id'], 'ad_creative_placement_lookup_idx');
        });

        DB::table('ad_creatives')
            ->whereNotNull('ad_placement_id')
            ->orderBy('id')
            ->each(function (object $creative): void {
                DB::table('ad_creative_placement')->updateOrInsert(
                    ['ad_creative_id' => $creative->id, 'ad_placement_id' => $creative->ad_placement_id],
                    ['created_at' => now(), 'updated_at' => now()],
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_creative_placement');
    }
};
