<?php

namespace App\Http\Controllers;

use App\Enums\GymStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Gym;
use App\Models\PlatformActivityLog;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class PlatformDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $subscriptionCounts = Subscription::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return Inertia::render('platform/dashboard', [
            'metrics' => [
                'gyms_total' => Gym::query()->count(),
                'users_total' => User::query()->where('is_platform_admin', false)->count(),
                'subscribers_total' => Subscription::query()->count(),
                'gyms_active' => Gym::query()->where('status', GymStatus::Active)->count(),
                'gyms_suspended' => Gym::query()->where('status', GymStatus::Suspended)->count(),
                'plans_active' => SaasPlan::query()->where('is_active', true)->count(),
                'subscriptions_trialing' => (int) ($subscriptionCounts[SubscriptionStatus::Trialing->value] ?? 0),
                'subscriptions_active' => (int) ($subscriptionCounts[SubscriptionStatus::Active->value] ?? 0),
                'subscriptions_attention' => collect([
                    SubscriptionStatus::PastDue,
                    SubscriptionStatus::Suspended,
                    SubscriptionStatus::Cancelled,
                    SubscriptionStatus::Expired,
                ])->sum(fn (SubscriptionStatus $status): int => (int) ($subscriptionCounts[$status->value] ?? 0)),
                'subscription_payments_pending' => SubscriptionPayment::query()
                    ->where('status', SubscriptionPaymentStatus::Pending)
                    ->count(),
            ],
            'recentGyms' => Gym::query()
                ->select(['id', 'subscription_id', 'name', 'slug', 'status', 'created_at'])
                ->with([
                    'subscription:id,subscriber_id,saas_plan_id,status,trial_ends_at,current_period_ends_at',
                    'subscription.plan:id,name',
                    'subscription.subscriber:id,name',
                ])
                ->latest('id')
                ->limit(6)
                ->get()
                ->map(fn (Gym $gym): array => [
                    'id' => $gym->getKey(),
                    'name' => $gym->name,
                    'slug' => $gym->slug,
                    'status' => $gym->status->value,
                    'status_label' => $gym->status->label(),
                    'subscription_status' => $gym->subscription?->effectiveStatus()->value,
                    'subscription_status_label' => $gym->subscription?->effectiveStatus()->label(),
                    'plan_name' => $gym->subscription?->plan?->name,
                    'subscriber_id' => $gym->subscription?->subscriber_id,
                    'subscriber_name' => $gym->subscription?->subscriber?->name,
                    'created_at' => $gym->created_at?->toIso8601String(),
                ]),
            'recentActivity' => PlatformActivityLog::query()
                ->select(['id', 'actor_id', 'event', 'properties', 'created_at'])
                ->with('actor:id,name')
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (PlatformActivityLog $log): array => [
                    'id' => $log->getKey(),
                    'event' => $log->event,
                    'actor_name' => $log->actor_id === null ? 'Sistem' : $log->actor->name,
                    'properties' => $log->properties,
                    'created_at' => $log->created_at?->toIso8601String(),
                ]),
        ]);
    }
}
