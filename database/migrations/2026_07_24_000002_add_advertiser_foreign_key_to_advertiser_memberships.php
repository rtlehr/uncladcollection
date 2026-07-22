<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('advertiser_memberships') || ! Schema::hasTable('advertisers')) {
            return;
        }

        $foreignKeys = collect(Schema::getForeignKeys('advertiser_memberships'));
        $hasAdvertiserForeignKey = $foreignKeys->contains(function (array $foreignKey): bool {
            $columns = $foreignKey['columns'] ?? [];
            $foreignTable = $foreignKey['foreign_table'] ?? $foreignKey['foreignTable'] ?? null;

            return in_array('advertiser_id', $columns, true) && $foreignTable === 'advertisers';
        });

        if ($hasAdvertiserForeignKey) {
            return;
        }

        Schema::table('advertiser_memberships', function (Blueprint $table) {
            $table->foreign('advertiser_id')
                ->references('id')
                ->on('advertisers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('advertiser_memberships')) {
            return;
        }

        $foreignKeys = collect(Schema::getForeignKeys('advertiser_memberships'));
        $hasAdvertiserForeignKey = $foreignKeys->contains(function (array $foreignKey): bool {
            $columns = $foreignKey['columns'] ?? [];
            $foreignTable = $foreignKey['foreign_table'] ?? $foreignKey['foreignTable'] ?? null;

            return in_array('advertiser_id', $columns, true) && $foreignTable === 'advertisers';
        });

        if (! $hasAdvertiserForeignKey) {
            return;
        }

        Schema::table('advertiser_memberships', function (Blueprint $table) {
            $table->dropForeign(['advertiser_id']);
        });
    }
};
