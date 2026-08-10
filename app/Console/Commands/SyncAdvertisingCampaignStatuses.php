<?php

namespace App\Console\Commands;

use App\Advertising\AdvertisingCampaignLifecycleService;
use Illuminate\Console\Command;

class SyncAdvertisingCampaignStatuses extends Command
{
    protected $signature = 'advertising:sync-campaign-statuses';

    protected $description = 'Automatically activate due advertising campaigns and complete expired campaigns.';

    public function handle(AdvertisingCampaignLifecycleService $lifecycle): int
    {
        $result = $lifecycle->sync();

        $this->info(sprintf(
            'Advertising lifecycle synchronized: %d activated, %d completed, %d blocked by launch readiness.',
            $result['activated'],
            $result['completed'],
            $result['blocked'],
        ));

        return self::SUCCESS;
    }
}
