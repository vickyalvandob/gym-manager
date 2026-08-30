<?php

namespace App\Actions\Platform;

use App\Enums\SubscriptionStatus;
use App\Models\Gym;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Support\PlatformActivityLogger;
use App\Support\SubscriptionQuota;
use Illuminate\Support\Facades\DB;

class UpdateGymSubscription
{
    public function __construct(
        private readonly PlatformActivityLogger $activityLogger,
        private readonly SubscriptionQuota $subscriptionQuota,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(Gym $gym, SaasPlan $plan, array $attributes): Subscription
    {
        return DB::transaction(function () use ($gym, $plan, $attributes): Subscription {
            $lockedGym = Gym::query()
                ->whereKey($gym->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $subscription = $lockedGym->subscription()
                ->lockForUpdate()
                ->first();
            $previousStatus = $subscription?->status;
            $status = SubscriptionStatus::from((string) $attributes['status']);

            if ($subscription === null) {
                $subscriber = $lockedGym->users()
                    ->wherePivot('role', 'owner')
                    ->oldest('gym_user.created_at')
                    ->firstOrFail();
                $subscription = Subscription::query()
                    ->whereBelongsTo($subscriber, 'subscriber')
                    ->lockForUpdate()
                    ->first();

                if ($subscription === null) {
                    $subscription = new Subscription([
                        'subscriber_id' => $subscriber->getKey(),
                        'started_at' => now(),
                    ]);
                } else {
                    $this->subscriptionQuota->ensurePlanCoversUsage($subscription, $plan, 1);
                }
            } else {
                $this->subscriptionQuota->ensurePlanCoversUsage($subscription, $plan);
            }
            $subscription->fill([
                ...$attributes,
                'saas_plan_id' => $plan->getKey(),
                'suspended_at' => $status === SubscriptionStatus::Suspended ? now() : null,
                'cancelled_at' => $status === SubscriptionStatus::Cancelled ? now() : null,
            ]);
            $subscription->save();
            $lockedGym->forceFill(['subscription_id' => $subscription->getKey()])->save();
            $gym->setAttribute('subscription_id', $subscription->getKey());

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
