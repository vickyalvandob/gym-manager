<?php

namespace Database\Factories;

use App\Enums\MemberGender;
use App\Enums\MemberStatus;
use App\Models\Gym;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'gym_id' => Gym::factory(),
            'member_number' => 'MBR-'.fake()->unique()->numerify('######'),
            'name' => fake()->name(),
            'phone' => fake()->numerify('08##########'),
            'email' => fake()->optional()->safeEmail(),
            'gender' => fake()->randomElement(MemberGender::cases()),
            'birth_date' => fake()->optional()->dateTimeBetween('-55 years', '-16 years'),
            'address' => fake()->optional()->address(),
            'photo' => null,
            'emergency_contact' => fake()->optional()->numerify('08##########'),
            'status' => MemberStatus::Active,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
