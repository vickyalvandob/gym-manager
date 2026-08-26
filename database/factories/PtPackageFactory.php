<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\PtPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PtPackage>
 */
class PtPackageFactory extends Factory
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
            'name' => fake()->unique()->bothify('PT Package ###'),
            'session_count' => fake()->randomElement([1, 4, 8, 12]),
            'validity_days' => fake()->randomElement([null, 30, 60, 90]),
            'price' => (string) (fake()->numberBetween(3, 25) * 50000),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
