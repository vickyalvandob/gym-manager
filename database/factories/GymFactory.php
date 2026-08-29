<?php

namespace Database\Factories;

use App\Enums\GymStatus;
use App\Models\Gym;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Gym>
 */
class GymFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company().' Gym';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'status' => GymStatus::Active,
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'onboarding_completed_at' => now(),
            'membership_expiry_warning_days' => 7,
        ];
    }
}
