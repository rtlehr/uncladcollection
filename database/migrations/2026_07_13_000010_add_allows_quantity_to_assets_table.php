<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->boolean('allows_quantity')
                ->default(false)
                ->after('is_ai_generated')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropIndex(['allows_quantity']);
            $table->dropColumn('allows_quantity');
        });
    }
};
