<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('asset_trending_scores')) {
            Schema::create('asset_trending_scores', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
                $table->string('period', 32);
                $table->decimal('score', 14, 4)->default(0);
                $table->unsignedInteger('rank')->nullable();
                $table->json('components')->nullable();
                $table->timestamp('calculated_at');
                $table->timestamps();

                $table->unique(['asset_id', 'period']);
                $table->index(['period', 'rank']);
                $table->index(['period', 'score']);
            });
        }

        if (Schema::hasTable('assets')) {
            if (! Schema::hasColumn('assets', 'trending_boost')) {
                Schema::table('assets', function (Blueprint $table): void {
                    $table->integer('trending_boost')->default(0)->after('is_featured');
                });
            }

            if (! Schema::hasColumn('assets', 'suppress_from_trending')) {
                Schema::table('assets', function (Blueprint $table): void {
                    $table->boolean('suppress_from_trending')->default(false)->after('trending_boost');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assets')) {
            $columns = array_values(array_filter(
                ['trending_boost', 'suppress_from_trending'],
                fn (string $column): bool => Schema::hasColumn('assets', $column),
            ));

            if ($columns !== []) {
                Schema::table('assets', function (Blueprint $table) use ($columns): void {
                    $table->dropColumn($columns);
                });
            }
        }

        Schema::dropIfExists('asset_trending_scores');
    }
};
