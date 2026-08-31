<?php

namespace Database\Factories;

use App\Enums\SubscriptionPaymentStatus;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionPayment>
 */
class SubscriptionPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'saas_plan_id' => SaasPlan::factory(),
            'plan_name' => 'Growth',
            'amount' => '299000.00',
            'currency' => 'IDR',
            'billing_interval' => 'monthly',
            'reference_number' => fake()->bothify('TRX-########'),
            'proof_path' => 'subscription-payments/1/proof.jpg',
            'status' => SubscriptionPaymentStatus::Pending,
            'submitted_at' => now(),
            'reviewed_at' => null,
            'reviewed_by' => null,
            'review_notes' => null,
            'period_starts_at' => null,
            'period_ends_at' => null,
        ];
    }
}
