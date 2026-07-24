<?php

namespace Database\Factories;

use App\Enums\SupportTicketPriority;
use App\Models\SupportTicketCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SupportTicketCategory> */
class SupportTicketCategoryFactory extends Factory
{
    protected $model = SupportTicketCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        return [
            'name' => Str::headline($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'description' => fake()->sentence(),
            'default_priority' => SupportTicketPriority::Normal,
            'is_public' => true,
            'is_member' => true,
            'is_advertiser' => true,
            'is_active' => true,
            'sort_order' => 10,
        ];
    }
}
