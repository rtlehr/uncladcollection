<?php

namespace App\Console\Commands;

use App\Models\SearchTerm;
use App\Services\SearchIntelligence\SearchTermAggregationService;
use App\Services\SearchIntelligence\SearchTermAiService;
use Illuminate\Console\Command;
use Throwable;

class RebuildSearchTermIntelligence extends Command
{
    protected $signature = 'discovery:rebuild-search-intelligence {--days=} {--analyze-ai} {--limit=}';
    protected $description = 'Aggregate marketplace search terms and optionally request Qwen grouping suggestions.';

    public function handle(SearchTermAggregationService $aggregation, SearchTermAiService $ai): int
    {
        $summary = $aggregation->rebuild($this->option('days') ? (int) $this->option('days') : null);
        $this->info("Aggregated {$summary['events']} search events into {$summary['terms']} terms.");

        if (! $this->option('analyze-ai')) return self::SUCCESS;
        $limit = max(1, (int) ($this->option('limit') ?: config('search-intelligence.ai_batch_size', 20)));
        $terms = SearchTerm::query()->where('search_count', '>=', config('search-intelligence.ai_minimum_searches', 2))
            ->whereDoesntHave('mapping', fn ($q) => $q->whereIn('status', ['approved','pending']))
            ->orderByDesc('zero_result_count')->orderByDesc('search_count')->limit($limit)->get();
        foreach ($terms as $term) {
            try { $ai->analyze($term); $this->line("Analyzed: {$term->display_term}"); }
            catch (Throwable $e) { $this->warn("Skipped {$term->display_term}: {$e->getMessage()}"); }
        }
        return self::SUCCESS;
    }
}
