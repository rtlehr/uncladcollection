<?php

namespace Database\Seeders;

use App\Models\AiKeywordExclusion;
use Illuminate\Database\Seeder;

class AiKeywordExclusionSeeder extends Seeder
{
    public function run(): void
    {
        $exclusions = [
            [
                'keyword' => 'image',
                'notes' => 'Generic media term that adds little search value.',
            ],
            [
                'keyword' => 'photo',
                'notes' => 'Generic media term that adds little search value.',
            ],
            [
                'keyword' => 'photograph',
                'notes' => 'Generic media term that adds little search value.',
            ],
            [
                'keyword' => 'picture',
                'notes' => 'Generic media term that adds little search value.',
            ],
            [
                'keyword' => 'stock image',
                'notes' => 'Generic marketplace phrase rather than descriptive metadata.',
            ],
            [
                'keyword' => 'stock photo',
                'notes' => 'Generic marketplace phrase rather than descriptive metadata.',
            ],
            [
                'keyword' => 'digital image',
                'notes' => 'Generic format description that adds little search value.',
            ],
            [
                'keyword' => 'high resolution',
                'notes' => 'Generic technical phrase rather than descriptive metadata.',
            ],
        ];

        foreach ($exclusions as $exclusion) {
            AiKeywordExclusion::query()->updateOrCreate(
                [
                    'normalized_keyword' => AiKeywordExclusion::normalize($exclusion['keyword']),
                ],
                [
                    'keyword' => $exclusion['keyword'],
                    'is_active' => true,
                    'notes' => $exclusion['notes'],
                    'created_by' => null,
                ],
            );
        }
    }
}
