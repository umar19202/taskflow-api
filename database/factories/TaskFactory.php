<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id'  => Project::factory(),
            'created_by'  => User::factory(),
            'assigned_to' => null,
            'title'       => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status'      => 'open',
            'priority'    => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'due_date'    => fake()->optional()->date(),
        ];
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'done',
        ]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $user->id,
        ]);
    }
}
