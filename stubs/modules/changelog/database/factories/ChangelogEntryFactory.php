<?php

namespace Database\Factories;

use App\Models\ChangelogEntry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChangelogEntry>
 */
class ChangelogEntryFactory extends Factory
{
    protected $model = ChangelogEntry::class;

    public function definition(): array
    {
        return [
            'ulid' => Str::ulid()->toString(),
            'title' => fake()->sentence(5),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'category' => fake()->randomElement(['feature', 'improvement', 'fix', 'other']),
            'published_at' => fake()->dateTimeBetween('-3 months'),
            'is_published' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
