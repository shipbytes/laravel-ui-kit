<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketReply>
 */
class TicketReplyFactory extends Factory
{
    protected $model = TicketReply::class;

    public function definition(): array
    {
        return [
            'ticket_id' => SupportTicket::factory(),
            'user_id' => User::factory(),
            'message' => fake()->paragraph(),
            'is_admin_reply' => false,
        ];
    }

    public function fromAdmin(): static
    {
        return $this->state(fn () => ['is_admin_reply' => true]);
    }
}
