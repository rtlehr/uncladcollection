<?php

use App\Models\Asset;
use App\Models\AssetFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('downloads', function (Blueprint $table): void {
            $table->foreignId('asset_id')->nullable()->after('image_id')->constrained('assets')->nullOnDelete();
            $table->foreignId('asset_file_id')->nullable()->after('asset_id')->constrained('asset_files')->nullOnDelete();
            $table->uuid('batch_uuid')->nullable()->after('order_item_id');
            $table->string('source', 40)->default('customer')->after('download_type');
            $table->string('original_filename')->nullable()->after('source');
            $table->unsignedBigInteger('size_bytes')->nullable()->after('original_filename');
            $table->string('status', 30)->default('completed')->after('size_bytes');
            $table->text('failure_reason')->nullable()->after('status');

            $table->index(['asset_id', 'downloaded_at']);
            $table->index(['asset_file_id', 'downloaded_at']);
            $table->index('batch_uuid');
        });

        Schema::table('downloads', function (Blueprint $table): void {
            $table->foreignId('image_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('downloads', function (Blueprint $table): void {
            $table->dropForeign(['asset_file_id']);
            $table->dropForeign(['asset_id']);
            $table->dropIndex(['asset_file_id', 'downloaded_at']);
            $table->dropIndex(['asset_id', 'downloaded_at']);
            $table->dropIndex(['batch_uuid']);
            $table->dropColumn([
                'asset_id', 'asset_file_id', 'batch_uuid', 'source',
                'original_filename', 'size_bytes', 'status', 'failure_reason',
            ]);
        });

        Schema::table('downloads', function (Blueprint $table): void {
            $table->foreignId('image_id')->nullable(false)->change();
        });
    }
};
