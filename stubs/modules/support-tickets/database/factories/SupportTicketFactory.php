<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    public function definition(): array
    {
        return [
            'ulid' => Str::ulid()->toString(),
            'user_id' => User::factory(),
            'subject' => fake()->sentence(6),
            'category' => fake()->randomElement(['general', 'bug', 'feature_request', 'billing', 'other']),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'status' => 'open',
            'ip_address' => fake()->ipv4(),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }
}
