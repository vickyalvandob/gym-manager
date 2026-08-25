<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
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
            'invoice_number' => 'INV-'.now()->format('Ym').'-'.fake()->unique()->numerify('######'),
            'amount' => (string) (fake()->numberBetween(5, 50) * 50000),
            'method' => null,
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
            'notes' => null,
            'received_by_id' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'method' => PaymentMethod::Cash,
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'received_by_id' => User::factory(),
        ]);
    }
}
