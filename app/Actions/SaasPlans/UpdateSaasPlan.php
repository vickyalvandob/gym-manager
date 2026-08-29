<?php

namespace App\Actions\SaasPlans;

use App\Models\SaasPlan;
use App\Support\PlatformActivityLogger;
use Illuminate\Support\Facades\DB;

class UpdateSaasPlan
{
    public function __construct(private readonly PlatformActivityLogger $activityLogger) {}

    /** @param array<string, mixed> $attributes */
    public function handle(SaasPlan $plan, array $attributes): SaasPlan
    {
        return DB::transaction(function () use ($plan, $attributes): SaasPlan {
            $plan->fill($attributes);
            $changedFields = array_keys($plan->getDirty());
            $plan->save();

            $this->activityLogger->record('saas_plan.updated', $plan, [
                'name' => $plan->name,
                'fields' => $changedFields,
            ]);

            return $plan;
        }, 3);
    }
}
