<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DiscoveryCacheService;
use App\Services\UserAssetAffinityService;
use Illuminate\Console\Command;

class RebuildUserAssetAffinities extends Command
{
    protected $signature = 'discovery:rebuild-user-affinities {--user= : Rebuild one user ID only}';
    protected $description = 'Rebuild personalized discovery affinity profiles.';

    public function handle(UserAssetAffinityService $service, DiscoveryCacheService $cache): int
    {
        $query = User::query()->when($this->option('user'), fn ($q, $id) => $q->whereKey((int) $id));
        $count = 0;
        $query->chunkById(100, function ($users) use ($service, &$count): void {
            foreach ($users as $user) { $service->rebuild($user); $count++; }
        });
        $cache->invalidate();
        $this->info("Rebuilt {$count} user affinity profiles.");
        return self::SUCCESS;
    }
}
