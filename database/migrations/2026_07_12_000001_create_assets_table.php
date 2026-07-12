<?php

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('legacy_image_id')->nullable()->unique()->constrained('images')->nullOnDelete();
            $table->foreignId('collection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('asset_type', 40)->default(AssetType::Image->value)->index();
            $table->string('status', 40)->default(AssetStatus::Draft->value)->index();
            $table->string('photographer')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_ai_generated')->default(false)->index();
            $table->unsignedInteger('downloads_count')->default(0);
            $table->unsignedInteger('favorites_count')->default(0);
            $table->unsignedInteger('purchases_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('primary_preview_file_id')->nullable();
            $table->unsignedBigInteger('poster_file_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_active', 'published_at'], 'assets_publication_index');
            $table->index(['collection_id', 'is_active', 'sort_order'], 'assets_collection_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
