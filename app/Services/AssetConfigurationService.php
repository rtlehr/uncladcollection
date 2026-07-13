<?php

namespace App\Services;

use App\Commerce\Configuration\ConfigurationManager;
use App\Commerce\Configuration\ConfigurationSelection;
use App\Models\Asset;

/**
 * @deprecated Use App\Commerce\Configuration\ConfigurationManager for new code.
 */
class AssetConfigurationService
{
    public function __construct(
        private readonly ConfigurationManager $manager,
    ) {}

    public function saveMany(Asset $asset, array $groups): void
    {
        $this->manager->saveMany($asset, $groups);
    }

    public function calculateAdjustment(
        iterable $groups,
        array $selections,
        ?int $offeringId = null,
    ): int {
        return $this->manager->calculateAdjustment(
            $groups,
            ConfigurationSelection::fromNormalizedValues($selections),
            $offeringId,
        );
    }
}
