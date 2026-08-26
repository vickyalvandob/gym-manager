<?php

namespace Database\Factories;

use App\Enums\PtSessionStatus;
use App\Models\Gym;
use App\Models\MemberPtPackage;
use App\Models\PtSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PtSession>
 */
class PtSessionFactory extends Factory
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
            'member_pt_package_id' => fn (array $attributes): int => MemberPtPackage::factory()->create([
                'gym_id' => $attributes['gym_id'],
            ])->getKey(),
            'member_id' => fn (array $attributes): int => MemberPtPackage::query()
                ->findOrFail($attributes['member_pt_package_id'])
                ->member_id,
            'trainer_id' => fn (array $attributes): int => MemberPtPackage::query()
                ->findOrFail($attributes['member_pt_package_id'])
                ->trainer_id,
            'scheduled_at' => now()->addDay()->startOfHour(),
            'duration_minutes' => 60,
            'status' => PtSessionStatus::Scheduled,
            'completed_at' => null,
            'notes' => null,
            'cancellation_reason' => null,
            'quota_consumed' => false,
            'created_by' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => PtSessionStatus::Completed,
            'completed_at' => now(),
            'quota_consumed' => true,
        ]);
    }
}
