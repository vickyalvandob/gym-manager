<?php

namespace App\Actions\MembershipPlans;

use App\Models\MembershipPlan;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteMembershipPlan
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function handle(MembershipPlan $membershipPlan): void
    {
        DB::transaction(function () use ($membershipPlan): void {
            $lockedMembershipPlan = $this->gymContext->gym()->membershipPlans()
                ->whereKey($membershipPlan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedMembershipPlan->memberMemberships()->exists()) {
                throw ValidationException::withMessages([
                    'membership_plan' => 'Paket yang sudah memiliki riwayat membership tidak dapat dihapus.',
                ]);
            }

            $this->activityLogger->record('membership_plan.deleted', $lockedMembershipPlan, [
                'name' => $lockedMembershipPlan->name,
            ]);

            $lockedMembershipPlan->delete();
        }, 3);
    }
}
