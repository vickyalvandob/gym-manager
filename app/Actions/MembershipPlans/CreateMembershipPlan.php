<?php

namespace App\Actions\MembershipPlans;

use App\Models\MembershipPlan;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Illuminate\Support\Facades\DB;

class CreateMembershipPlan
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): MembershipPlan
    {
        return DB::transaction(function () use ($attributes): MembershipPlan {
            $membershipPlan = $this->gymContext->gym()->membershipPlans()->create($attributes);

            $this->activityLogger->record('membership_plan.created', $membershipPlan, [
                'name' => $membershipPlan->name,
                'duration' => $membershipPlan->duration,
                'duration_unit' => $membershipPlan->duration_unit->value,
                'price' => $membershipPlan->price,
                'is_active' => $membershipPlan->is_active,
            ]);

            return $membershipPlan;
        }, 3);
    }
}
