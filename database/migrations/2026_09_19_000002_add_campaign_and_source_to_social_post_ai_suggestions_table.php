<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_post_ai_suggestions', function (Blueprint $table): void {
            $table->string('campaign', 150)->nullable()->index()->after('subject');
            $table->foreignId('source_suggestion_id')
                ->nullable()
                ->after('social_post_id')
                ->constrained('social_post_ai_suggestions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('social_post_ai_suggestions', function (Blueprint $table): void {
            $table->dropForeign(['source_suggestion_id']);
            $table->dropColumn(['campaign', 'source_suggestion_id']);
        });
    }
};
