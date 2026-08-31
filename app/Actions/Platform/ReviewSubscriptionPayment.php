<?php

namespace App\Actions\Platform;

use App\Enums\SaasPlanInterval;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Support\PlatformActivityLogger;
use App\Support\SubscriptionQuota;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewSubscriptionPayment
{
    public function __construct(
        private readonly PlatformActivityLogger $activityLogger,
        private readonly SubscriptionQuota $subscriptionQuota,
    ) {}

    public function handle(
        SubscriptionPayment $subscriptionPayment,
        User $reviewer,
        SubscriptionPaymentStatus $decision,
        ?string $reviewNotes,
    ): SubscriptionPayment {
        return DB::transaction(function () use ($subscriptionPayment, $reviewer, $decision, $reviewNotes): SubscriptionPayment {
            $payment = SubscriptionPayment::query()
                ->with('plan')
                ->whereKey($subscriptionPayment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status !== SubscriptionPaymentStatus::Pending) {
                throw ValidationException::withMessages([
                    'decision' => 'Pembayaran ini sudah ditinjau sebelumnya.',
                ]);
            }

            $subscription = Subscription::query()
                ->whereKey($payment->subscription_id)
                ->lockForUpdate()
                ->firstOrFail();

            $periodStartsAt = null;
            $periodEndsAt = null;

            if ($decision === SubscriptionPaymentStatus::Approved) {
                $this->subscriptionQuota->ensurePlanCoversUsage($subscription, $payment->plan);
                $periodStartsAt = $this->periodStartsAt($subscription);
                $periodEndsAt = match ($payment->billing_interval) {
                    SaasPlanInterval::Monthly => $periodStartsAt->copy()->addMonthNoOverflow(),
                    SaasPlanInterval::Yearly => $periodStartsAt->copy()->addYearNoOverflow(),
                };

                $subscription->forceFill([
                    'saas_plan_id' => $payment->saas_plan_id,
                    'status' => SubscriptionStatus::Active,
                    'trial_ends_at' => null,
                    'current_period_starts_at' => $periodStartsAt,
                    'current_period_ends_at' => $periodEndsAt,
                    'suspended_at' => null,
                    'cancelled_at' => null,
                ])->save();
            }

            $payment->forceFill([
                'status' => $decision,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->getKey(),
                'review_notes' => $reviewNotes,
                'period_starts_at' => $periodStartsAt,
                'period_ends_at' => $periodEndsAt,
            ])->save();

            $this->activityLogger->record('subscription_payment.'.$decision->value, $payment, [
                'subscriber_id' => $subscription->subscriber_id,
                'subscription_id' => $subscription->getKey(),
                'amount' => $payment->amount,
                'currency' => $payment->currency,
            ]);

            return $payment->load(['subscription.subscriber', 'reviewer']);
        }, 3);
    }

    private function periodStartsAt(Subscription $subscription): CarbonInterface
    {
        if ($subscription->current_period_ends_at?->isFuture()) {
            return $subscription->current_period_ends_at->copy();
        }

        if ($subscription->status === SubscriptionStatus::Trialing
            && $subscription->trial_ends_at?->isFuture()) {
            return $subscription->trial_ends_at->copy();
        }

        return now();
    }
}
