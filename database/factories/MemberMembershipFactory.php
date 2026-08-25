<?php

namespace Database\Factories;

use App\Enums\MembershipDurationUnit;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberMembership>
 */
class MemberMembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = today()->subDays(fake()->numberBetween(0, 30));

        return [
            'gym_id' => Gym::factory(),
            'member_id' => fn (array $attributes): int => Member::factory()->create([
                'gym_id' => $attributes['gym_id'],
            ])->getKey(),
            'membership_plan_id' => fn (array $attributes): int => MembershipPlan::factory()->create([
                'gym_id' => $attributes['gym_id'],
            ])->getKey(),
            'renewed_from_id' => null,
            'plan_name' => fake()->words(2, true),
            'duration' => 1,
            'duration_unit' => MembershipDurationUnit::Month,
            'price' => (string) (fake()->numberBetween(5, 50) * 50000),
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addMonth()->subDay(),
        ];
    }
}
