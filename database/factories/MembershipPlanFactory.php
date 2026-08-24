<?php

namespace Database\Factories;

use App\Enums\MembershipDurationUnit;
use App\Models\Gym;
use App\Models\MembershipPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipPlan>
 */
class MembershipPlanFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'gym_id' => Gym::factory(),
            'name' => fake()->unique()->words(2, true),
            'duration' => fake()->randomElement([1, 3, 6, 12]),
            'duration_unit' => MembershipDurationUnit::Month,
            'price' => (string) (fake()->numberBetween(5, 50) * 50000),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
