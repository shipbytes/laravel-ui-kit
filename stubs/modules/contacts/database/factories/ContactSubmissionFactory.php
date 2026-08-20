<?php

namespace Database\Factories;

use App\Models\ContactSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactSubmission>
 */
class ContactSubmissionFactory extends Factory
{
    protected $model = ContactSubmission::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'subject' => fake()->sentence(5),
            'message' => fake()->paragraphs(2, true),
            'status' => 'unread',
            'ip_address' => fake()->ipv4(),
        ];
    }

    public function replied(): static
    {
        return $this->state(fn () => [
            'status' => 'replied',
            'reply' => fake()->paragraph(),
            'replied_at' => now(),
        ]);
    }
}
