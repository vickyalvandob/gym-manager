<?php

namespace App\Actions\MembershipPlans;

use App\Models\MembershipPlan;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;

class UpdateMembershipPlanStatus
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function handle(MembershipPlan $membershipPlan, bool $isActive): MembershipPlan
    {
        if ($membershipPlan->is_active === $isActive) {
            return $membershipPlan;
        }

        return DB::transaction(function () use ($membershipPlan, $isActive): MembershipPlan {
            $previousStatus = $membershipPlan->is_active;
            $membershipPlan->update(['is_active' => $isActive]);

            $this->activityLogger->record('membership_plan.status_changed', $membershipPlan, [
                'name' => $membershipPlan->name,
                'from' => $previousStatus,
                'to' => $isActive,
            ]);

            return $membershipPlan;
        }, 3);
    }
}
