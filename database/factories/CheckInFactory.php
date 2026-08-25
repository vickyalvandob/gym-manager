<?php

namespace Database\Factories;

use App\Models\CheckIn;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckIn>
 */
class CheckInFactory extends Factory
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
            'member_id' => fn (array $attributes): int => Member::factory()->create([
                'gym_id' => $attributes['gym_id'],
            ])->getKey(),
            'member_membership_id' => fn (array $attributes): int => MemberMembership::factory()->create([
                'gym_id' => $attributes['gym_id'],
                'member_id' => $attributes['member_id'],
            ])->getKey(),
            'checked_in_at' => now(),
            'created_by' => User::factory(),
        ];
    }
}
