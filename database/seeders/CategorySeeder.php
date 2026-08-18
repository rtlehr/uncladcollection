<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [

            /*
            |--------------------------------------------------------------------------
            | Image Categories
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Lifestyle',
                'category_type' => 'image',
                'description' => 'Everyday nudist lifestyle photography.',
            ],

            [
                'name' => 'Beach',
                'category_type' => 'image',
                'description' => 'Beach and waterfront imagery.',
            ],

            [
                'name' => 'Resort',
                'category_type' => 'image',
                'description' => 'Nudist resort and vacation photography.',
            ],

            [
                'name' => 'Holiday',
                'category_type' => 'image',
                'description' => 'Holidays and festive occasions.',
            ],

            [
                'name' => 'Presntation',
                'category_type' => 'image',
                'description' => 'Presentation and public speaking images.',
            ],

            [
                'name' => 'Community',
                'category_type' => 'image',
                'description' => 'Community events and social activities.',
            ],

            [
                'name' => 'Travel',
                'category_type' => 'image',
                'description' => 'Travel destinations and experiences.',
            ],

            [
                'name' => 'Family',
                'category_type' => 'image',
                'description' => 'Family-friendly lifestyle photography.',
            ],

            [
                'name' => 'Couples',
                'category_type' => 'image',
                'description' => 'Couples and relationship-focused imagery.',
            ],

            [
                'name' => 'Artistic',
                'category_type' => 'image',
                'description' => 'Creative and artistic photography.',
            ],

            /*
            |--------------------------------------------------------------------------
            | Blog Categories
            |--------------------------------------------------------------------------
            */

            [
                'name' => 'Getting Started',
                'category_type' => 'blog',
                'description' => 'Introduction to naturism and nudism.',
            ],

            [
                'name' => 'Lifestyle',
                'category_type' => 'blog',
                'description' => 'Living the nudist lifestyle.',
            ],

            [
                'name' => 'Travel',
                'category_type' => 'blog',
                'description' => 'Travel guides and destination reviews.',
            ],

            [
                'name' => 'Resorts',
                'category_type' => 'blog',
                'description' => 'Resort reviews and information.',
            ],

            [
                'name' => 'Health & Wellness',
                'category_type' => 'blog',
                'description' => 'Health, fitness, and wellness topics.',
            ],

            [
                'name' => 'Community News',
                'category_type' => 'blog',
                'description' => 'Community updates and announcements.',
            ],

            [
                'name' => 'Photography',
                'category_type' => 'blog',
                'description' => 'Photography tips and image licensing.',
            ],
        ];

        foreach ($categories as $index => $category) {

            Category::updateOrCreate(
                [
                    'name' => $category['name'],
                    'category_type' => $category['category_type'],
                ],
                [
                    'slug' => Str::slug(
                        $category['category_type'] . '-' . $category['name']
                    ),
                    'description' => $category['description'],
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ]
            );
        }
    }
}