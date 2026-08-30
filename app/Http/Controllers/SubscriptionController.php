<?php

namespace App\Http\Controllers;

use App\Enums\GymRole;
use App\Support\GymContext;
use App\Support\SubscriptionQuota;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly SubscriptionQuota $subscriptionQuota,
    ) {}

    public function __invoke(): Response
    {
        abort_unless($this->gymContext->role() === GymRole::Owner, HttpResponse::HTTP_FORBIDDEN);

        $gym = $this->gymContext->gym()->load('subscription.plan');
        $subscription = $gym->subscription;

        return Inertia::render('subscription/show', [
            'gym' => [
                'id' => $gym->getKey(),
                'name' => $gym->name,
                'status' => $gym->status->value,
                'status_label' => $gym->status->label(),
            ],
            'subscription' => $subscription === null ? null : [
                'id' => $subscription->getKey(),
                'status' => $subscription->status->value,
                'status_label' => $subscription->status->label(),
                'plan_name' => $subscription->plan->name,
                'price' => $subscription->plan->price,
                'currency' => $subscription->plan->currency,
                'billing_interval_label' => $subscription->plan->billing_interval->label(),
                'usage' => $this->subscriptionQuota->usage($subscription),
                'limits' => [
                    'gyms' => $subscription->plan->max_gyms,
                    'members' => $subscription->plan->max_members,
                    'staff' => $subscription->plan->max_staff,
                ],
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'current_period_starts_at' => $subscription->current_period_starts_at?->toIso8601String(),
                'current_period_ends_at' => $subscription->current_period_ends_at?->toIso8601String(),
                'grants_access' => $subscription->grantsAccess(),
            ],
        ]);
    }
}
