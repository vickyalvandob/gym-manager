<?php

namespace Database\Factories;

use App\Enums\MemberPtPackageStatus;
use App\Enums\PaymentStatus;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberPtPackage;
use App\Models\PtPackage;
use App\Models\Trainer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberPtPackage>
 */
class MemberPtPackageFactory extends Factory
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
            'trainer_id' => fn (array $attributes): int => Trainer::factory()->create([
                'gym_id' => $attributes['gym_id'],
            ])->getKey(),
            'pt_package_id' => fn (array $attributes): int => PtPackage::factory()->create([
                'gym_id' => $attributes['gym_id'],
            ])->getKey(),
            'total_sessions' => 8,
            'used_sessions' => 0,
            'start_date' => today(),
            'expires_at' => today()->addDays(60),
            'price' => '900000.00',
            'status' => MemberPtPackageStatus::Active,
            'payment_status' => PaymentStatus::Paid,
            'notes' => null,
            'created_by' => null,
        ];
    }
}
