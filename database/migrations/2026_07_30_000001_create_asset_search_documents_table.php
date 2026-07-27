<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_search_documents', function (Blueprint $table): void {
            $table->foreignId('asset_id')->primary()->constrained('assets')->cascadeOnDelete();
            $table->string('normalized_title')->default('');
            $table->text('search_text');
            $table->string('orientation', 20)->nullable()->index();
            $table->unsignedInteger('width')->nullable()->index();
            $table->unsignedInteger('height')->nullable()->index();
            $table->timestamp('indexed_at')->nullable()->index();
            $table->timestamps();
            $table->fullText(['normalized_title', 'search_text'], 'asset_search_documents_fulltext');
        });

        DB::table('assets')->orderBy('id')->chunkById(250, function ($assets): void {
            $now = now();
            foreach ($assets as $asset) {
                DB::table('asset_search_documents')->insert([
                    'asset_id' => $asset->id,
                    'normalized_title' => mb_strtolower(trim((string) $asset->title)),
                    'search_text' => mb_strtolower(trim(implode(' ', array_filter([
                        $asset->title, $asset->description, $asset->photographer,
                        $asset->alt_text ?? null, $asset->seo_title ?? null, $asset->seo_description ?? null,
                    ])))),
                    'indexed_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_search_documents');
    }
};
