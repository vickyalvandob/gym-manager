<?php

namespace Database\Factories;

use App\Enums\SaasPlanInterval;
use App\Models\SaasPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SaasPlan>
 */
class SaasPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Plan';

        return [
            'name' => Str::headline($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'description' => fake()->sentence(),
            'price' => fake()->randomElement(['299000.00', '599000.00', '999000.00']),
            'currency' => 'IDR',
            'billing_interval' => SaasPlanInterval::Monthly,
            'trial_days' => 14,
            'max_gyms' => 1,
            'max_members' => 500,
            'max_staff' => 10,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
