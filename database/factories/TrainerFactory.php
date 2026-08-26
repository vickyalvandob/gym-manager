<?php

namespace Database\Factories;

use App\Enums\TrainerStatus;
use App\Models\Gym;
use App\Models\Trainer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trainer>
 */
class TrainerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gym_id' => Gym::factory(),
            'user_id' => null,
            'trainer_code' => 'TRN-'.fake()->unique()->numerify('######'),
            'name' => fake()->name(),
            'phone' => fake()->numerify('0812########'),
            'email' => fake()->unique()->safeEmail(),
            'specialization' => fake()->randomElement([
                'Strength & Conditioning',
                'Functional Training',
                'Weight Loss',
            ]),
            'bio' => null,
            'status' => TrainerStatus::Active,
            'joined_at' => today(),
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => TrainerStatus::Inactive,
        ]);
    }
}
