<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('design_exports', function (Blueprint $table): void {
            $table->string('render_engine', 30)->nullable()->after('status');
            $table->timestamp('queued_at')->nullable()->after('render_engine');
            $table->timestamp('started_at')->nullable()->after('queued_at');
        });
    }

    public function down(): void
    {
        Schema::table('design_exports', function (Blueprint $table): void {
            $table->dropColumn(['render_engine', 'queued_at', 'started_at']);
        });
    }
};
