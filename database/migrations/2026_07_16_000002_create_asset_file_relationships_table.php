<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_file_relationships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('source_asset_file_id')
                ->constrained('asset_files')
                ->cascadeOnDelete();
            $table->foreignId('target_asset_file_id')
                ->constrained('asset_files')
                ->cascadeOnDelete();
            $table->string('relationship_type', 50)->index();
            $table->string('label', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                [
                    'source_asset_file_id',
                    'target_asset_file_id',
                    'relationship_type',
                ],
                'asset_file_relationship_unique',
            );

            $table->index(
                ['asset_id', 'relationship_type', 'sort_order'],
                'asset_file_relationship_asset_type_sort',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_file_relationships');
    }
};
