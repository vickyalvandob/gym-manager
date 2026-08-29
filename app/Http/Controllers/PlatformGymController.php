<?php

namespace App\Http\Controllers;

use App\Enums\GymStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Requests\IndexPlatformGymRequest;
use App\Models\Gym;
use App\Models\SaasPlan;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PlatformGymController extends Controller
{
    public function index(IndexPlatformGymRequest $request): Response
    {
        $search = Str::squish((string) $request->validated('search', ''));
        $status = $request->validated('status');
        $subscriptionStatus = $request->validated('subscription_status');

        $gyms = Gym::query()
            ->select(['id', 'name', 'slug', 'status', 'onboarding_completed_at', 'created_at'])
            ->withCount('users')
            ->with(['subscription:id,gym_id,saas_plan_id,status,trial_ends_at,current_period_ends_at', 'subscription.plan:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when(is_string($status), fn ($query) => $query->where('status', $status))
            ->when(is_string($subscriptionStatus), fn ($query) => $query->whereHas(
                'subscription',
                fn ($query) => $query->where('status', $subscriptionStatus),
            ))
            ->latest('id')
            ->paginate((int) $request->validated('per_page', 15))
            ->withQueryString()
            ->through(fn (Gym $gym): array => $this->gymData($gym));

        return Inertia::render('platform/gyms/index', [
            'gyms' => $gyms,
            'filters' => [
                'search' => $search,
                'status' => is_string($status) ? $status : '',
                'subscription_status' => is_string($subscriptionStatus) ? $subscriptionStatus : '',
                'per_page' => (int) $request->validated('per_page', 15),
            ],
            'gymStatusOptions' => $this->gymStatusOptions(),
            'subscriptionStatusOptions' => $this->subscriptionStatusOptions(),
        ]);
    }

    public function show(Gym $gym): Response
    {
        $gym->load([
            'subscription.plan:id,name,billing_interval,price,currency',
            'users' => fn ($query) => $query->select(['users.id', 'users.name', 'users.email'])->oldest('gym_user.created_at'),
        ])->loadCount(['members', 'users', 'trainers']);

        return Inertia::render('platform/gyms/show', [
            'gym' => $this->gymData($gym, true),
            'plans' => SaasPlan::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name', 'price', 'currency', 'billing_interval'])
                ->map(fn (SaasPlan $plan): array => [
                    'id' => $plan->getKey(),
                    'name' => $plan->name,
                    'price' => $plan->price,
                    'currency' => $plan->currency,
                    'billing_interval' => $plan->billing_interval->value,
                    'billing_interval_label' => $plan->billing_interval->label(),
                ]),
            'gymStatusOptions' => $this->gymStatusOptions(),
            'subscriptionStatusOptions' => $this->subscriptionStatusOptions(),
        ]);
    }

    /** @return array<string, mixed> */
    private function gymData(Gym $gym, bool $detailed = false): array
    {
        $data = [
            'id' => $gym->getKey(),
            'name' => $gym->name,
            'slug' => $gym->slug,
            'status' => $gym->status->value,
            'status_label' => $gym->status->label(),
            'onboarding_completed_at' => $gym->onboarding_completed_at?->toIso8601String(),
            'users_count' => (int) ($gym->users_count ?? 0),
            'members_count' => (int) ($gym->members_count ?? 0),
            'trainers_count' => (int) ($gym->trainers_count ?? 0),
            'created_at' => $gym->created_at?->toIso8601String(),
            'subscription' => $gym->subscription === null ? null : [
                'id' => $gym->subscription->getKey(),
                'status' => $gym->subscription->status->value,
                'status_label' => $gym->subscription->status->label(),
                'plan_id' => $gym->subscription->saas_plan_id,
                'plan_name' => $gym->subscription->plan->name,
                'trial_ends_at' => $gym->subscription->trial_ends_at?->toIso8601String(),
                'current_period_starts_at' => $gym->subscription->current_period_starts_at?->toIso8601String(),
                'current_period_ends_at' => $gym->subscription->current_period_ends_at?->toIso8601String(),
                'grants_access' => $gym->subscription->grantsAccess(),
            ],
        ];

        if ($detailed) {
            $data['users'] = $gym->users->map(fn ($user): array => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'role' => (string) $user->getRelation('pivot')->getAttribute('role'),
            ]);
        }

        return $data;
    }

    /** @return array<int, array{value: string, label: string}> */
    private function gymStatusOptions(): array
    {
        return array_map(fn (GymStatus $status): array => [
            'value' => $status->value,
            'label' => $status->label(),
        ], GymStatus::cases());
    }

    /** @return array<int, array{value: string, label: string}> */
    private function subscriptionStatusOptions(): array
    {
        return array_map(fn (SubscriptionStatus $status): array => [
            'value' => $status->value,
            'label' => $status->label(),
        ], SubscriptionStatus::cases());
    }
}
