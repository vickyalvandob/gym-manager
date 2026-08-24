<?php

namespace App\Actions\MembershipPlans;

use App\Models\MembershipPlan;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;

class UpdateMembershipPlan
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    /** @param array<string, mixed> $attributes */
    public function handle(MembershipPlan $membershipPlan, array $attributes): MembershipPlan
    {
        return DB::transaction(function () use ($membershipPlan, $attributes): MembershipPlan {
            $membershipPlan->fill($attributes);
            $changedFields = array_keys($membershipPlan->getDirty());
            $membershipPlan->save();

            $this->activityLogger->record('membership_plan.updated', $membershipPlan, [
                'name' => $membershipPlan->name,
                'fields' => $changedFields,
            ]);

            return $membershipPlan;
        }, 3);
    }
}
