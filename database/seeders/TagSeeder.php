<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            'image' => [
                'Nature',
                'Landscape',
                'Beach',
                'Portrait',
                'Wildlife',
                'Black and White',
                'Outdoor',
                'Studio',
            ],

            'blog' => [
                'News',
                'Travel',
                'Technology',
                'Tutorials',
                'Reviews',
                'Photography',
                'Updates',
                'Announcements',
            ],
        ];

        foreach ($tags as $type => $tagNames) {
            foreach ($tagNames as $name) {
                Tag::updateOrCreate(
                    [
                        'slug' => Str::slug($name),
                        'tag_type' => $type,
                    ],
                    [
                        'name' => $name,
                        'description' => null,
                    ]
                );
            }
        }
    }
}