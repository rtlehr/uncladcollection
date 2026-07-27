<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Services\AssetSearchDocumentService;
use Illuminate\Console\Command;

class RebuildAssetSearchDocuments extends Command
{
    protected $signature = 'assets:rebuild-search {--asset= : Rebuild one asset ID}';
    protected $description = 'Rebuild normalized marketplace asset search documents';

    public function handle(AssetSearchDocumentService $documents): int
    {
        $query = Asset::query()->withTrashed();
        if ($this->option('asset')) $query->whereKey((int) $this->option('asset'));
        $total = (clone $query)->count();
        $bar = $this->output->createProgressBar($total);
        $query->orderBy('id')->chunkById(100, function ($assets) use ($documents, $bar): void {
            foreach ($assets as $asset) { $documents->rebuild($asset); $bar->advance(); }
        });
        $bar->finish(); $this->newLine();
        $this->info("Rebuilt {$total} asset search documents.");
        return self::SUCCESS;
    }
}
