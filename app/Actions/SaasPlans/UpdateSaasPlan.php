<?php

namespace App\Actions\SaasPlans;

use App\Models\SaasPlan;
use App\Support\PlatformActivityLogger;
use App\Support\SubscriptionQuota;
use Illuminate\Support\Facades\DB;

class UpdateSaasPlan
{
    public function __construct(
        private readonly PlatformActivityLogger $activityLogger,
        private readonly SubscriptionQuota $subscriptionQuota,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(SaasPlan $plan, array $attributes): SaasPlan
    {
        return DB::transaction(function () use ($plan, $attributes): SaasPlan {
            $lockedPlan = SaasPlan::query()
                ->whereKey($plan->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedPlan->fill($attributes);

            $lockedPlan->subscriptions()
                ->lockForUpdate()
                ->get()
                ->each(fn ($subscription) => $this->subscriptionQuota
                    ->ensurePlanCoversUsage($subscription, $lockedPlan, errorField: null));

            $changedFields = array_keys($lockedPlan->getDirty());
            $lockedPlan->save();

            $this->activityLogger->record('saas_plan.updated', $lockedPlan, [
                'name' => $lockedPlan->name,
                'fields' => $changedFields,
            ]);

            return $lockedPlan;
        }, 3);
    }
}
