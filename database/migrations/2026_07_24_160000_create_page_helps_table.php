<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('page_helps', function (Blueprint $table) {
   $table->id(); $table->string('page_key',160)->index(); $table->string('title');
   $table->text('summary')->nullable(); $table->longText('content');
   $table->string('audience',30)->default('all')->index();
   $table->boolean('is_active')->default(true)->index(); $table->boolean('is_published')->default(false)->index();
   $table->timestamp('published_at')->nullable()->index(); $table->unsignedInteger('sort_order')->default(0);
   $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
   $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
   $table->timestamps();
   $table->index(['page_key','is_active','is_published'],'page_help_lookup_idx');
  });
 }
 public function down(): void { Schema::dropIfExists('page_helps'); }
};
