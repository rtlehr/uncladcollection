<?php

namespace Database\Factories;

use App\Enums\SupportTicketMessageType;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupportTicketMessage> */
class SupportTicketMessageFactory extends Factory
{
    protected $model = SupportTicketMessage::class;

    public function definition(): array
    {
        return [
            'support_ticket_id' => SupportTicket::factory(),
            'user_id' => User::factory(),
            'author_name' => fake()->name(),
            'author_email' => fake()->safeEmail(),
            'message_type' => SupportTicketMessageType::CustomerMessage,
            'body' => fake()->paragraph(),
            'is_customer_visible' => true,
        ];
    }

    public function internalNote(): static
    {
        return $this->state(fn () => [
            'message_type' => SupportTicketMessageType::InternalNote,
            'is_customer_visible' => false,
        ]);
    }
}
