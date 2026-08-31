<?php

namespace App\Actions\Platform;

use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Support\PlatformActivityLogger;
use App\Support\SubscriptionQuota;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateSubscriberSubscription
{
    public function __construct(
        private readonly PlatformActivityLogger $activityLogger,
        private readonly SubscriptionQuota $subscriptionQuota,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(User $subscriber, SaasPlan $plan, array $attributes): Subscription
    {
        return DB::transaction(function () use ($subscriber, $plan, $attributes): Subscription {
            $subscription = Subscription::query()
                ->whereBelongsTo($subscriber, 'subscriber')
                ->lockForUpdate()
                ->firstOrFail();
            $previousStatus = $subscription->status;
            $status = SubscriptionStatus::from((string) $attributes['status']);

            if ($subscription->payments()
                ->where('status', SubscriptionPaymentStatus::Pending)
                ->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'Review pembayaran pending untuk mengubah status dan periode subscription.',
                ]);
            }

            $this->subscriptionQuota->ensurePlanCoversUsage($subscription, $plan);

            $subscription->fill([
                ...$attributes,
                'saas_plan_id' => $plan->getKey(),
                'suspended_at' => $status === SubscriptionStatus::Suspended ? now() : null,
                'cancelled_at' => $status === SubscriptionStatus::Cancelled ? now() : null,
            ]);
            $subscription->save();

            $this->activityLogger->record('subscription.updated', $subscription, [
                'subscriber_id' => $subscriber->getKey(),
                'plan' => $plan->name,
                'from' => $previousStatus->value,
                'to' => $status->value,
            ]);

            return $subscription->load('plan');
        }, 3);
    }
}
