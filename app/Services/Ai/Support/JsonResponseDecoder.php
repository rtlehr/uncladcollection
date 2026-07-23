<?php

namespace App\Services\Ai\Support;

use JsonException;
use RuntimeException;

final class JsonResponseDecoder
{
    /** @return array<string, mixed> */
    public static function decode(string $text): array
    {
        $candidates = self::candidates($text);
        $lastError = null;

        foreach ($candidates as $candidate) {
            try {
                $decoded = json_decode($candidate, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                $lastError = $exception;
                continue;
            }

            if (! is_array($decoded)) {
                $lastError = new JsonException('Decoded JSON is not an object.');
                continue;
            }

            return AssetMetadataSchema::normalize($decoded);
        }

        throw new RuntimeException(
            'The AI returned metadata that could not be decoded as JSON'.
            ($lastError instanceof JsonException ? ': '.$lastError->getMessage() : '.'),
            previous: $lastError,
        );
    }

    /** @return list<string> */
    private static function candidates(string $text): array
    {
        $trimmed = trim($text);
        $withoutFence = preg_replace('/^```(?:json)?\s*/i', '', $trimmed) ?? $trimmed;
        $withoutFence = preg_replace('/\s*```$/', '', $withoutFence) ?? $withoutFence;

        $candidates = array_values(array_unique(array_filter([
            $trimmed,
            trim($withoutFence),
            self::extractFirstJsonObject($trimmed),
            self::extractFirstJsonObject($withoutFence),
        ], static fn (?string $value): bool => is_string($value) && trim($value) !== '')));

        return $candidates;
    }

    private static function extractFirstJsonObject(string $text): ?string
    {
        $length = strlen($text);

        for ($start = 0; $start < $length; $start++) {
            if ($text[$start] !== '{') {
                continue;
            }

            $depth = 0;
            $insideString = false;
            $escaped = false;

            for ($index = $start; $index < $length; $index++) {
                $character = $text[$index];

                if ($insideString) {
                    if ($escaped) {
                        $escaped = false;
                    } elseif ($character === '\\') {
                        $escaped = true;
                    } elseif ($character === '"') {
                        $insideString = false;
                    }
                    continue;
                }

                if ($character === '"') {
                    $insideString = true;
                    continue;
                }

                if ($character === '{') {
                    $depth++;
                } elseif ($character === '}') {
                    $depth--;

                    if ($depth === 0) {
                        return substr($text, $start, $index - $start + 1);
                    }
                }
            }
        }

        return null;
    }
}
