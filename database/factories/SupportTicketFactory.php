<?php

namespace Database\Factories;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketSource;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\SupportTicketCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SupportTicket> */
class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'ticket_number' => 'UC-'.fake()->unique()->numberBetween(100001, 999999),
            'user_id' => User::factory(),
            'category_id' => SupportTicketCategory::factory(),
            'status' => SupportTicketStatus::New,
            'priority' => SupportTicketPriority::Normal,
            'source' => SupportTicketSource::Member,
            'subject' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'last_customer_reply_at' => now(),
        ];
    }

    public function guest(): static
    {
        return $this->state(fn () => [
            'user_id' => null,
            'guest_name' => fake()->name(),
            'guest_email' => fake()->safeEmail(),
            'source' => SupportTicketSource::Public,
        ]);
    }
}
