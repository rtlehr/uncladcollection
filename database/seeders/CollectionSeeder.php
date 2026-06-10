<?php

namespace Database\Seeders;

use App\Models\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        $collections = [
            [
                'name' => 'Featured Collection',
                'description' => 'A curated collection of featured images.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Beach Photography',
                'description' => 'Images captured in beach and coastal settings.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Black and White',
                'description' => 'A collection focused on black and white photography.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Outdoor Lifestyle',
                'description' => 'Images focused on outdoor lifestyle themes.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Studio Work',
                'description' => 'Images captured in controlled studio settings.',
                'sort_order' => 5,
            ],
        ];

        foreach ($collections as $collection) {
            Collection::updateOrCreate(
                [
                    'slug' => Str::slug($collection['name']),
                ],
                [
                    'name' => $collection['name'],
                    'description' => $collection['description'],
                    'sort_order' => $collection['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}