<?php

namespace App\Services\Ai\ContentStudio;

use App\Models\AiPromptExample;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PromptExampleImporter
{
    public function import(string $path, ?int $userId = null): array
    {
        $decoded = json_decode((string) file_get_contents($path), true);
        $items = $decoded['prompts'] ?? data_get($decoded, 'unclad_collection_prompts.content_prompts');

        if (! is_array($items)) {
            throw new RuntimeException('JSON must contain a prompts array.');
        }

        $sourceFilename = basename($path);
        $usedSourceIndexes = AiPromptExample::query()
            ->where('source_filename', $sourceFilename)
            ->whereNotNull('source_index')
            ->pluck('source_index')
            ->mapWithKeys(fn ($value) => [(int) $value => true])
            ->all();

        $stats = ['created' => 0, 'duplicates' => 0, 'invalid' => 0];

        DB::transaction(function () use ($items, $sourceFilename, $userId, &$stats, &$usedSourceIndexes): void {
            foreach ($items as $index => $item) {
                $title = trim((string) ($item['title'] ?? ''));
                $content = trim((string) ($item['content'] ?? ''));

                if ($title === '' || $content === '') {
                    $stats['invalid']++;
                    continue;
                }

                $normalized = AiPromptExample::normalize($content);

                if (AiPromptExample::where('normalized_content', $normalized)->exists()) {
                    $stats['duplicates']++;
                    continue;
                }

                $sourceIndex = isset($item['source_index']) ? (int) $item['source_index'] : (int) $index;

                while (isset($usedSourceIndexes[$sourceIndex])) {
                    $sourceIndex++;
                }

                $usedSourceIndexes[$sourceIndex] = true;

                AiPromptExample::create([
                    'title' => $title,
                    'content' => $content,
                    'category' => $item['category'] ?? null,
                    'content_context' => $item['content_context'] ?? 'general',
                    'intended_uses' => $item['intended_uses'] ?? [],
                    'subject_tags' => $item['subject_tags'] ?? [],
                    'is_family_friendly' => $item['is_family_friendly'] ?? true,
                    'is_enabled' => $item['is_enabled'] ?? true,
                    'source_filename' => $sourceFilename,
                    'source_index' => $sourceIndex,
                    'created_by' => $userId,
                    'normalized_content' => $normalized,
                ]);

                $stats['created']++;
            }
        });

        return $stats;
    }
}
