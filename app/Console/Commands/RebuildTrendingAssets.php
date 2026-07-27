<?php

namespace App\Console\Commands;

use App\Services\TrendingAssetService;
use Illuminate\Console\Command;

class RebuildTrendingAssets extends Command
{
    protected $signature = 'assets:rebuild-trending {--period= : Rebuild only now, week, or month}';
    protected $description = 'Recalculate time-decayed marketplace trending rankings';

    public function handle(TrendingAssetService $service): int
    {
        $period = $this->option('period');
        $allowed = array_keys(config('discovery.trending.periods', []));

        if ($period && ! in_array($period, $allowed, true)) {
            $this->error('Unknown period. Allowed values: '.implode(', ', $allowed));
            return self::FAILURE;
        }

        foreach ($service->rebuild($period ?: null) as $name => $count) {
            $this->info("{$name}: {$count} ranked assets");
        }

        return self::SUCCESS;
    }
}
