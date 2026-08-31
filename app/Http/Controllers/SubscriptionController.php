<?php

namespace App\Http\Controllers;

use App\Enums\GymRole;
use App\Enums\SubscriptionPaymentStatus;
use App\Models\PlatformBillingSetting;
use App\Models\SaasPlan;
use App\Models\SubscriptionPayment;
use App\Support\GymContext;
use App\Support\SubscriptionQuota;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly SubscriptionQuota $subscriptionQuota,
    ) {}

    public function __invoke(Request $request): Response
    {
        abort_unless($this->gymContext->role() === GymRole::Owner, HttpResponse::HTTP_FORBIDDEN);

        $gym = $this->gymContext->gym()->load([
            'subscription.plan',
            'subscription.payments' => fn ($query) => $query
                ->with('reviewer:id,name')
                ->latest('id')
                ->limit(20),
        ]);
        $subscription = $gym->subscription;
        $usage = $subscription === null ? ['gyms' => 0, 'members' => 0, 'staff' => 0] : $this->subscriptionQuota->usage($subscription);
        $activeTab = in_array($request->string('tab')->toString(), ['overview', 'plans', 'payment', 'history'], true)
            ? $request->string('tab')->toString()
            : 'overview';
        $plans = SaasPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $requestedPlanId = $request->integer('plan_id');
        $currentActivePlan = $subscription === null
            ? null
            : $plans->firstWhere('id', $subscription->saas_plan_id);
        $selectedPlan = $plans->firstWhere('id', $requestedPlanId)
            ?? ($currentActivePlan !== null && (float) $currentActivePlan->price > 0 ? $currentActivePlan : null)
            ?? $plans->first(fn (SaasPlan $plan): bool => (float) $plan->price > 0);
        $billingSettings = PlatformBillingSetting::query()->find(1);
        $hasPendingPayment = $subscription?->payments->contains(
            fn (SubscriptionPayment $payment): bool => $payment->status === SubscriptionPaymentStatus::Pending,
        ) ?? false;

        return Inertia::render('subscription/show', [
            'gym' => [
                'id' => $gym->getKey(),
                'name' => $gym->name,
                'status' => $gym->status->value,
                'status_label' => $gym->status->label(),
            ],
            'subscription' => $subscription === null ? null : [
                'id' => $subscription->getKey(),
                'status' => $subscription->effectiveStatus()->value,
                'status_label' => $subscription->effectiveStatus()->label(),
                'plan_name' => $subscription->plan->name,
                'price' => $subscription->plan->price,
                'currency' => $subscription->plan->currency,
                'billing_interval_label' => $subscription->plan->billing_interval->label(),
                'usage' => $usage,
                'limits' => [
                    'gyms' => $subscription->plan->max_gyms,
                    'members' => $subscription->plan->max_members,
                    'staff' => $subscription->plan->max_staff,
                ],
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'current_period_starts_at' => $subscription->current_period_starts_at?->toIso8601String(),
                'current_period_ends_at' => $subscription->current_period_ends_at?->toIso8601String(),
                'grants_access' => $subscription->grantsAccess(),
                'has_pending_payment' => $hasPendingPayment,
                'payments' => $subscription->payments->map(fn (SubscriptionPayment $payment): array => [
                    'id' => $payment->getKey(),
                    'plan_name' => $payment->plan_name,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'billing_interval_label' => $payment->billing_interval->label(),
                    'reference_number' => $payment->reference_number,
                    'status' => $payment->status->value,
                    'status_label' => $payment->status->label(),
                    'submitted_at' => $payment->submitted_at->toIso8601String(),
                    'reviewed_at' => $payment->reviewed_at?->toIso8601String(),
                    'reviewer_name' => $payment->reviewer?->name,
                    'review_notes' => $payment->review_notes,
                    'period_starts_at' => $payment->period_starts_at?->toIso8601String(),
                    'period_ends_at' => $payment->period_ends_at?->toIso8601String(),
                ])->values(),
            ],
            'activeTab' => $activeTab,
            'plans' => $plans->map(fn (SaasPlan $plan): array => [
                'id' => $plan->getKey(),
                'name' => $plan->name,
                'description' => $plan->description,
                'price' => $plan->price,
                'currency' => $plan->currency,
                'billing_interval_label' => $plan->billing_interval->label(),
                'max_gyms' => $plan->max_gyms,
                'max_members' => $plan->max_members,
                'max_staff' => $plan->max_staff,
                'is_current' => $subscription?->saas_plan_id === $plan->getKey(),
                'can_select' => $this->planCoversUsage($plan, $usage),
            ])->values(),
            'selectedPlan' => $selectedPlan === null ? null : [
                'id' => $selectedPlan->getKey(),
                'name' => $selectedPlan->name,
                'price' => $selectedPlan->price,
                'currency' => $selectedPlan->currency,
                'billing_interval_label' => $selectedPlan->billing_interval->label(),
                'can_select' => $this->planCoversUsage($selectedPlan, $usage),
            ],
            'canSubmitPayment' => $subscription !== null
                && $selectedPlan !== null
                && $subscription->subscriber_id === $request->user()?->getKey()
                && (float) $selectedPlan->price > 0
                && $this->planCoversUsage($selectedPlan, $usage)
                && ! $hasPendingPayment,
            'billing' => [
                'bank_name' => $billingSettings?->bank_name,
                'account_name' => $billingSettings?->account_name,
                'account_number' => $billingSettings?->account_number,
                'instructions' => $billingSettings?->instructions,
                'is_configured' => $billingSettings?->isConfigured() ?? false,
            ],
        ]);
    }

    /** @param array{gyms: int, members: int, staff: int} $usage */
    private function planCoversUsage(SaasPlan $plan, array $usage): bool
    {
        return ($plan->max_gyms === null || $usage['gyms'] <= $plan->max_gyms)
            && ($plan->max_members === null || $usage['members'] <= $plan->max_members)
            && ($plan->max_staff === null || $usage['staff'] <= $plan->max_staff);
    }
}
