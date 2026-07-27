<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_pages', function (Blueprint $table): void {
            $table->string('header_image_original_path')->nullable()->after('content');
            $table->string('header_image_path')->nullable()->after('header_image_original_path');
            $table->json('header_image_edit')->nullable()->after('header_image_path');
            $table->string('header_image_alt')->nullable()->after('header_image_edit');
            $table->string('legal_version', 40)->nullable()->after('canonical_url');
            $table->date('effective_date')->nullable()->after('legal_version');
            $table->date('revised_date')->nullable()->after('effective_date');
        });
    }

    public function down(): void
    {
        Schema::table('public_pages', function (Blueprint $table): void {
            $table->dropColumn([
                'header_image_original_path', 'header_image_path', 'header_image_edit',
                'header_image_alt', 'legal_version', 'effective_date', 'revised_date',
            ]);
        });
    }
};
