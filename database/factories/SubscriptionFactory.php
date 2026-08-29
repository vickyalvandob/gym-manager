<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Gym;
use App\Models\SaasPlan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
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
            'saas_plan_id' => SaasPlan::factory(),
            'status' => SubscriptionStatus::Active,
            'started_at' => now()->subMonth(),
            'trial_ends_at' => null,
            'current_period_starts_at' => now()->startOfMonth(),
            'current_period_ends_at' => now()->addMonth(),
            'suspended_at' => null,
            'cancelled_at' => null,
        ];
    }

    public function trialing(): static
    {
        return $this->state(fn (): array => [
            'status' => SubscriptionStatus::Trialing,
            'started_at' => now(),
            'trial_ends_at' => now()->addDays(14),
            'current_period_starts_at' => null,
            'current_period_ends_at' => null,
        ]);
    }
}
