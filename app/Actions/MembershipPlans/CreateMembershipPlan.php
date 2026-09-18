<?php

namespace App\Actions\MembershipPlans;

use App\Models\MembershipPlan;
use App\Models\Subscription;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use App\Support\SubscriptionQuota;
use Illuminate\Support\Facades\DB;

class CreateMembershipPlan
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
        private readonly SubscriptionQuota $subscriptionQuota,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): MembershipPlan
    {
        return DB::transaction(function () use ($attributes): MembershipPlan {
            $subscriptionId = $this->gymContext->gym()->subscription_id;
            if ($subscriptionId !== null) {
                $subscription = Subscription::query()->with('plan')->whereKey($subscriptionId)
                    ->lockForUpdate()->firstOrFail();
                $this->subscriptionQuota->ensureCanCreateMembershipPlan($subscription);
            }

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
