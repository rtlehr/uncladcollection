<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('design_exports', function (Blueprint $table): void {
            $table->string('original_filename')->nullable()->after('path');
            $table->string('mime_type', 100)->nullable()->after('original_filename');
            $table->unsignedBigInteger('size_bytes')->nullable()->after('mime_type');
            $table->string('preset_name', 80)->nullable()->after('fit_mode');
        });
    }

    public function down(): void
    {
        Schema::table('design_exports', function (Blueprint $table): void {
            $table->dropColumn(['original_filename', 'mime_type', 'size_bytes', 'preset_name']);
        });
    }
};
