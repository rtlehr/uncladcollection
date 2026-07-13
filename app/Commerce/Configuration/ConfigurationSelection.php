<?php

namespace App\Commerce\Configuration;

use App\Models\AssetConfigurationGroup;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

final class ConfigurationSelection
{
    /**
     * @param array<string, mixed> $values
     * @param array<int, array{group:string, values:array<int, string>}> $labels
     */
    private function __construct(
        private readonly array $values,
        private readonly array $labels,
    ) {}

    /** @param iterable<AssetConfigurationGroup> $groups */
    public static function fromInput(iterable $groups, array $input, string $errorPrefix = 'selections'): self
    {
        $values = [];
        $labels = [];

        foreach ($groups as $group) {
            if (! $group->is_active) {
                continue;
            }

            $raw = $input[$group->code] ?? null;
            $type = $group->display_type->value;

            if (in_array($type, ['text', 'number'], true)) {
                $value = is_scalar($raw) ? trim((string) $raw) : '';

                if ($group->is_required && $value === '') {
                    throw ValidationException::withMessages([
                        "$errorPrefix.{$group->code}" => "{$group->name} is required.",
                    ]);
                }

                if ($value !== '') {
                    $values[$group->code] = $value;
                    $labels[] = ['group' => $group->name, 'values' => [$value]];
                }

                continue;
            }

            $selected = array_values(array_filter(
                Arr::wrap($raw),
                fn ($value) => $value !== null && $value !== '',
            ));

            $allowedValues = $group->values
                ->where('is_active', true)
                ->pluck('value')
                ->all();

            $selected = array_values(array_intersect($selected, $allowedValues));

            if ($group->is_required && $selected === []) {
                throw ValidationException::withMessages([
                    "$errorPrefix.{$group->code}" => "{$group->name} is required.",
                ]);
            }

            if ($selected === []) {
                continue;
            }

            $storedValue = $group->allows_multiple ? $selected : $selected[0];
            $values[$group->code] = $storedValue;

            $valueLabels = $group->values
                ->whereIn('value', Arr::wrap($storedValue))
                ->pluck('label')
                ->values()
                ->all();

            $labels[] = [
                'group' => $group->name,
                'values' => $valueLabels !== [] ? $valueLabels : Arr::wrap($storedValue),
            ];
        }

        ksort($values);

        return new self($values, $labels);
    }

    /**
     * Rehydrates an immutable selection from a persisted cart snapshot.
     * Labels are preserved so renamed catalog options do not rewrite cart history.
     */
    public static function fromSnapshot(array $snapshot): self
    {
        $values = (array) ($snapshot['selections'] ?? []);
        ksort($values);

        return new self($values, array_values((array) ($snapshot['labels'] ?? [])));
    }

    /** @param array<string, mixed> $values */
    public static function fromNormalizedValues(array $values, array $labels = []): self
    {
        ksort($values);

        return new self($values, array_values($labels));
    }

    /** @return array<string, mixed> */
    public function values(): array
    {
        return $this->values;
    }

    /** @return array<int, array{group:string, values:array<int, string>}> */
    public function labels(): array
    {
        return $this->labels;
    }

    public function hash(): string
    {
        $normalized = collect($this->values)->map(function ($value) {
            if (is_array($value)) {
                sort($value);
            }

            return $value;
        })->all();

        return hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR));
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->hash(), $other->hash());
    }

    public function toSnapshotArray(): array
    {
        return [
            'version' => 1,
            'selections' => $this->values,
            'labels' => $this->labels,
        ];
    }
}
