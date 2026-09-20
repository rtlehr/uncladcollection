<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_post_ai_suggestions', function (Blueprint $table): void {
            $table->json('prompt_additions')->nullable()->after('campaign');
        });
    }

    public function down(): void
    {
        Schema::table('social_post_ai_suggestions', function (Blueprint $table): void {
            $table->dropColumn('prompt_additions');
        });
    }
};
