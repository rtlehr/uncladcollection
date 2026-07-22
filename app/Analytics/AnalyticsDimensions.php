<?php

namespace App\Analytics;

final class AnalyticsDimensions
{
    public static function sanitize(array $dimensions): array
    {
        return self::sanitizeArray($dimensions, 0);
    }

    private static function sanitizeArray(array $values, int $depth): array
    {
        if ($depth >= config('analytics.maximum_dimension_depth', 4)) {
            return [];
        }

        $limit = max(1, config('analytics.maximum_dimension_items', 50));
        $sanitized = [];

        foreach (array_slice($values, 0, $limit, true) as $key => $value) {
            $key = mb_substr((string) $key, 0, 80);

            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value, $depth + 1);
            } elseif (is_string($value)) {
                $sanitized[$key] = mb_substr($value, 0, config('analytics.maximum_dimension_string_length', 500));
            } elseif (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
                $sanitized[$key] = $value;
            } elseif ($value instanceof \Stringable) {
                $sanitized[$key] = mb_substr((string) $value, 0, config('analytics.maximum_dimension_string_length', 500));
            }
        }

        ksort($sanitized);

        return $sanitized;
    }
}
