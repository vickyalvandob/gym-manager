<?php

namespace App\Actions\Platform;

use App\Enums\SubscriptionStatus;
use App\Models\Gym;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Support\PlatformActivityLogger;
use Illuminate\Support\Facades\DB;

class UpdateGymSubscription
{
    public function __construct(private readonly PlatformActivityLogger $activityLogger) {}

    /** @param array<string, mixed> $attributes */
    public function handle(Gym $gym, SaasPlan $plan, array $attributes): Subscription
    {
        return DB::transaction(function () use ($gym, $plan, $attributes): Subscription {
            $lockedGym = Gym::query()
                ->whereKey($gym->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $subscription = Subscription::query()
                ->whereBelongsTo($lockedGym)
                ->lockForUpdate()
                ->first();
            $previousStatus = $subscription?->status;
            $status = SubscriptionStatus::from((string) $attributes['status']);

            $subscription ??= new Subscription([
                'gym_id' => $lockedGym->getKey(),
                'started_at' => now(),
            ]);
            $subscription->fill([
                ...$attributes,
                'saas_plan_id' => $plan->getKey(),
                'suspended_at' => $status === SubscriptionStatus::Suspended ? now() : null,
                'cancelled_at' => $status === SubscriptionStatus::Cancelled ? now() : null,
            ]);
            $subscription->save();

            $this->activityLogger->record('subscription.updated', $subscription, [
                'gym_id' => $lockedGym->getKey(),
                'plan' => $plan->name,
                'from' => $previousStatus?->value,
                'to' => $status->value,
            ]);

            return $subscription->load('plan');
        }, 3);
    }
}
