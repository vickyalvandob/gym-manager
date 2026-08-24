<?php

namespace App\Actions\MembershipPlans;

use App\Models\MembershipPlan;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;

class DeleteMembershipPlan
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function handle(MembershipPlan $membershipPlan): void
    {
        DB::transaction(function () use ($membershipPlan): void {
            $this->activityLogger->record('membership_plan.deleted', $membershipPlan, [
                'name' => $membershipPlan->name,
            ]);

            $membershipPlan->delete();
        }, 3);
    }
}
