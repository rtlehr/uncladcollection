<?php

namespace App\Advertising;

use App\Models\AdvertisingCampaign;
use App\Services\AdminActivityService;
use Illuminate\Support\Facades\DB;

class AdvertisingCampaignLifecycleService
{
    public function __construct(
        private readonly AdvertiserWorkflowService $workflow,
        private readonly AdminActivityService $activity,
    ) {}

    /**
     * Synchronize scheduled and expired campaign statuses.
     *
     * @return array{activated:int,completed:int,blocked:int}
     */
    public function sync(): array
    {
        $result = [
            'activated' => 0,
            'completed' => 0,
            'blocked' => 0,
        ];

        // Expiration wins over activation so a scheduled campaign whose entire
        // run window has already passed is completed instead of briefly activated.
        AdvertisingCampaign::query()
            ->whereIn('status', ['scheduled', 'active', 'paused'])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($campaigns) use (&$result): void {
                foreach ($campaigns as $campaign) {
                    $changed = DB::transaction(function () use ($campaign): bool {
                        $locked = AdvertisingCampaign::query()->lockForUpdate()->find($campaign->id);

                        if (! $locked
                            || ! in_array($locked->status, ['scheduled', 'active', 'paused'], true)
                            || ! $locked->ends_at
                            || $locked->ends_at->isFuture()) {
                            return false;
                        }

                        $oldStatus = $locked->status;
                        $locked->update(['status' => 'completed']);

                        $this->logStatusChange(
                            $locked,
                            $oldStatus,
                            'completed',
                            'Campaign automatically completed because its scheduled end time was reached.',
                        );

                        return true;
                    });

                    if ($changed) {
                        $result['completed']++;
                    }
                }
            });

        AdvertisingCampaign::query()
            ->where('status', 'scheduled')
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', now())
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->orderBy('id')
            ->chunkById(100, function ($campaigns) use (&$result): void {
                foreach ($campaigns as $campaign) {
                    $outcome = DB::transaction(function () use ($campaign): string {
                        $locked = AdvertisingCampaign::query()->lockForUpdate()->find($campaign->id);

                        if (! $locked
                            || $locked->status !== 'scheduled'
                            || ! $locked->starts_at
                            || $locked->starts_at->isFuture()
                            || ($locked->ends_at && ! $locked->ends_at->isFuture())) {
                            return 'skipped';
                        }

                        $readiness = $this->workflow->launchReadiness($locked);

                        if (! $readiness['ready']) {
                            return 'blocked';
                        }

                        $locked->update(['status' => 'active']);

                        $this->logStatusChange(
                            $locked,
                            'scheduled',
                            'active',
                            'Campaign automatically activated because its scheduled start time was reached.',
                        );

                        return 'activated';
                    });

                    if ($outcome === 'activated') {
                        $result['activated']++;
                    } elseif ($outcome === 'blocked') {
                        $result['blocked']++;
                    }
                }
            });

        return $result;
    }

    private function logStatusChange(
        AdvertisingCampaign $campaign,
        string $oldStatus,
        string $newStatus,
        string $description,
    ): void {
        $this->activity->log(
            action: 'campaign_status',
            subject: $campaign,
            fieldName: 'status',
            oldValue: $oldStatus,
            newValue: $newStatus,
            description: $description,
        );
    }
}
