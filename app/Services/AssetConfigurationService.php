<?php

namespace App\Services;

use App\Enums\AssetConfigurationDisplayType;
use App\Enums\AssetConfigurationRuleType;
use App\Models\Asset;
use App\Models\AssetConfigurationGroup;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssetConfigurationService
{
    public function saveMany(Asset $asset, array $groups): void
    {
        DB::transaction(function () use ($asset, $groups): void {
            // Configuration is draftable catalog data. Purchase selections snapshot labels/values,
            // so replacing the builder state can safely remove the previous definition.
            $asset->configurationGroups()->withTrashed()->forceDelete();

            foreach (array_values($groups) as $groupIndex => $groupData) {
                $displayType = AssetConfigurationDisplayType::from($groupData['display_type']);
                $group = $asset->configurationGroups()->create([
                    'name' => $groupData['name'],
                    'code' => $this->uniqueCode($asset, $groupData['code'] ?? $groupData['name'], $groupIndex),
                    'display_type' => $displayType,
                    'is_required' => (bool) ($groupData['is_required'] ?? false),
                    'allows_multiple' => $displayType === AssetConfigurationDisplayType::Checkbox
                        ? (bool) ($groupData['allows_multiple'] ?? true)
                        : false,
                    'placeholder' => Arr::get($groupData, 'placeholder'),
                    'help_text' => Arr::get($groupData, 'help_text'),
                    'minimum_value' => Arr::get($groupData, 'minimum_value'),
                    'maximum_value' => Arr::get($groupData, 'maximum_value'),
                    'step_value' => Arr::get($groupData, 'step_value'),
                    'sort_order' => ($groupIndex + 1) * 10,
                    'is_active' => (bool) ($groupData['is_active'] ?? true),
                ]);

                if (! $displayType->usesValues()) {
                    continue;
                }

                foreach (array_values($groupData['values'] ?? []) as $valueIndex => $valueData) {
                    $value = $group->values()->create([
                        'label' => $valueData['label'],
                        'value' => Str::slug($valueData['value'] ?? $valueData['label'], '_') ?: 'value_'.($valueIndex + 1),
                        'description' => Arr::get($valueData, 'description'),
                        'swatch_color' => Arr::get($valueData, 'swatch_color'),
                        'image_path' => Arr::get($valueData, 'image_path'),
                        'is_default' => (bool) ($valueData['is_default'] ?? false),
                        'is_active' => (bool) ($valueData['is_active'] ?? true),
                        'sort_order' => ($valueIndex + 1) * 10,
                    ]);

                    $adjustment = (int) ($valueData['price_adjustment_cents'] ?? 0);
                    if ($adjustment !== 0) {
                        $value->rules()->create([
                            'rule_type' => AssetConfigurationRuleType::FixedAdjustment,
                            'amount_cents' => $adjustment,
                            'currency' => strtoupper($valueData['currency'] ?? 'USD'),
                            'is_active' => true,
                        ]);
                    }
                }
            }
        });
    }

    public function calculateAdjustment(array $groups, array $selections, ?int $offeringId = null): int
    {
        $adjustment = 0;

        foreach ($groups as $group) {
            $selected = Arr::wrap($selections[$group->code] ?? []);
            foreach ($group->values as $value) {
                if (! in_array($value->value, $selected, true)) {
                    continue;
                }

                foreach ($value->rules->where('is_active', true) as $rule) {
                    if ($rule->asset_offering_id !== null && $rule->asset_offering_id !== $offeringId) {
                        continue;
                    }
                    if ($rule->rule_type === AssetConfigurationRuleType::FixedAdjustment) {
                        $adjustment += $rule->amount_cents;
                    }
                }
            }
        }

        return $adjustment;
    }

    private function uniqueCode(Asset $asset, string $source, int $index): string
    {
        $base = Str::slug($source, '_') ?: 'option_'.($index + 1);
        $code = $base;
        $counter = 2;
        while ($asset->configurationGroups()->withTrashed()->where('code', $code)->exists()) {
            $code = $base.'_'.$counter++;
        }
        return $code;
    }
}
