<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_page_faq_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('public_page_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->longText('answer');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamps();
            $table->index(['public_page_id', 'is_active', 'sort_order'], 'page_faq_active_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_page_faq_items');
    }
};
