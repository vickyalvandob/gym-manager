<?php

namespace App\Actions\SaasPlans;

use App\Models\SaasPlan;
use App\Support\PlatformActivityLogger;
use Illuminate\Support\Facades\DB;

class UpdateSaasPlanStatus
{
    public function __construct(private readonly PlatformActivityLogger $activityLogger) {}

    public function handle(SaasPlan $plan, bool $isActive): SaasPlan
    {
        if ($plan->is_active === $isActive) {
            return $plan;
        }

        return DB::transaction(function () use ($plan, $isActive): SaasPlan {
            $previousStatus = $plan->is_active;
            $plan->update(['is_active' => $isActive]);

            $this->activityLogger->record('saas_plan.status_changed', $plan, [
                'name' => $plan->name,
                'from' => $previousStatus,
                'to' => $isActive,
            ]);

            return $plan;
        }, 3);
    }
}
