<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('eyebrow')->nullable();
            $table->text('introduction')->nullable();
            $table->longText('content')->nullable();
            $table->string('page_type', 32)->default('standard');
            $table->string('status', 20)->default('draft');
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->string('navigation_label')->nullable();
            $table->json('navigation_locations')->nullable();
            $table->unsignedInteger('sort_order')->default(100);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_active', 'published_at'], 'public_pages_publish_idx');
            $table->index(['sort_order', 'title'], 'public_pages_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_pages');
    }
};
