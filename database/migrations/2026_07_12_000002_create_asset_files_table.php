<?php

use App\Enums\AssetFileProcessingStatus;
use App\Enums\AssetFileScanStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('role', 40)->index();
            $table->string('media_type', 40)->index();
            $table->string('disk', 80);
            $table->string('directory', 1024)->default('');
            $table->string('stored_filename', 255);
            $table->string('original_filename', 255);
            $table->string('extension', 20)->index();
            $table->string('mime_type', 150)->nullable()->index();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->char('checksum_sha256', 64)->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->decimal('duration_seconds', 12, 3)->nullable();
            $table->unsignedInteger('page_count')->nullable();
            $table->json('metadata')->nullable();
            $table->string('processing_status', 40)->default(AssetFileProcessingStatus::Pending->value)->index();
            $table->string('virus_scan_status', 40)->default(AssetFileScanStatus::Pending->value)->index();
            $table->boolean('is_downloadable')->default(true)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_legacy')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();

            // Filenames may legitimately repeat in separate role directories.
            // UUID remains the immutable unique identifier for each file record.
            $table->index(
                ['asset_id', 'disk', 'stored_filename'],
                'asset_files_storage_lookup_index'
            );
            $table->index(['asset_id', 'is_active', 'sort_order'], 'asset_files_asset_sort_index');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->foreign('primary_preview_file_id')->references('id')->on('asset_files')->nullOnDelete();
            $table->foreign('poster_file_id')->references('id')->on('asset_files')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['primary_preview_file_id']);
            $table->dropForeign(['poster_file_id']);
        });

        Schema::dropIfExists('asset_files');
    }
};
