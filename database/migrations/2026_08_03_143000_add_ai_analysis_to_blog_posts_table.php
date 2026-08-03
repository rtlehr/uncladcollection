<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->json('ai_analysis')->nullable()->after('seo_description');
            $table->json('ai_analysis_settings')->nullable()->after('ai_analysis');
            $table->timestamp('ai_analyzed_at')->nullable()->after('ai_analysis_settings');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->dropColumn(['ai_analysis', 'ai_analysis_settings', 'ai_analyzed_at']);
        });
    }
};
