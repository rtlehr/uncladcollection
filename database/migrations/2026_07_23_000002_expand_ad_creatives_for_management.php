<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ad_creatives', function (Blueprint $table) {
            $table->foreignId('ad_placement_id')->nullable()->after('advertising_campaign_id')->constrained()->nullOnDelete();
            $table->string('original_media_path')->nullable()->after('media_path');
            $table->json('media_edit_data')->nullable()->after('original_media_path');
            $table->string('original_filename')->nullable()->after('mime_type');
            $table->timestamp('submitted_at')->nullable()->after('alt_text');
            $table->index(['advertising_campaign_id', 'status'], 'ad_creatives_campaign_status_idx');
            $table->index(['ad_placement_id', 'status'], 'ad_creatives_placement_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ad_creatives', function (Blueprint $table) {
            $table->dropIndex('ad_creatives_campaign_status_idx');
            $table->dropIndex('ad_creatives_placement_status_idx');
            $table->dropConstrainedForeignId('ad_placement_id');
            $table->dropColumn(['original_media_path', 'media_edit_data', 'original_filename', 'submitted_at']);
        });
    }
};
