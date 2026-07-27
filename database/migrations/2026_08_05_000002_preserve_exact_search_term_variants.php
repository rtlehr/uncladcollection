<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('search_term_variants')) {
            return;
        }

        if (! Schema::hasColumn('search_term_variants', 'raw_term_hash')) {
            Schema::table('search_term_variants', function (Blueprint $table): void {
                $table->char('raw_term_hash', 64)->nullable()->after('raw_term');
            });
        }

        DB::table('search_term_variants')
            ->select(['id', 'raw_term'])
            ->whereNull('raw_term_hash')
            ->orderBy('id')
            ->chunkById(250, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('search_term_variants')
                        ->where('id', $row->id)
                        ->update(['raw_term_hash' => hash('sha256', (string) $row->raw_term)]);
                }
            });

        // MySQL may use the old composite unique index to support the
        // search_term_id foreign key. Give that foreign key its own index
        // before replacing the unique constraint.
        if (! $this->indexExists('search_term_variants', 'search_term_variants_search_term_id_index')) {
            Schema::table('search_term_variants', function (Blueprint $table): void {
                $table->index('search_term_id', 'search_term_variants_search_term_id_index');
            });
        }

        if ($this->indexExists('search_term_variants', 'search_term_variant_unique')) {
            Schema::table('search_term_variants', function (Blueprint $table): void {
                $table->dropUnique('search_term_variant_unique');
            });
        }

        if (! $this->indexExists('search_term_variants', 'search_term_variant_raw_unique')) {
            Schema::table('search_term_variants', function (Blueprint $table): void {
                $table->unique(
                    ['search_term_id', 'raw_term_hash'],
                    'search_term_variant_raw_unique',
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('search_term_variants')) {
            return;
        }

        if ($this->indexExists('search_term_variants', 'search_term_variant_raw_unique')) {
            Schema::table('search_term_variants', function (Blueprint $table): void {
                $table->dropUnique('search_term_variant_raw_unique');
            });
        }

        // Exact variants may normalize to the same value, so do not recreate
        // the former lossy unique index during rollback.
        if (Schema::hasColumn('search_term_variants', 'raw_term_hash')) {
            Schema::table('search_term_variants', function (Blueprint $table): void {
                $table->dropColumn('raw_term_hash');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::connection()->getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
