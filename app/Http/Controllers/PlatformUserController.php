<?php

namespace App\Http\Controllers;

use App\Enums\GymRole;
use App\Enums\SubscriptionPaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Requests\IndexPlatformUserRequest;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Support\SubscriptionQuota;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PlatformUserController extends Controller
{
    public function __construct(private readonly SubscriptionQuota $subscriptionQuota) {}

    public function index(IndexPlatformUserRequest $request): Response
    {
        $search = Str::squish((string) $request->validated('search', ''));
        $accountType = $request->validated('account_type');
        $status = $request->validated('status');
        $planId = $request->validated('plan_id');
        $billingStatus = $request->validated('billing_status');

        $users = User::query()
            ->select(['id', 'name', 'email', 'email_verified_at', 'is_platform_admin', 'is_active', 'created_at'])
            ->with([
                'subscription' => fn ($query) => $query
                    ->select([
                        'id',
                        'subscriber_id',
                        'saas_plan_id',
                        'status',
                        'trial_ends_at',
                        'current_period_ends_at',
                    ])
                    ->withCount([
                        'payments as pending_payments_count' => fn ($query) => $query
                            ->where('status', SubscriptionPaymentStatus::Pending),
                    ]),
                'subscription.plan:id,name',
            ])
            ->withCount([
                'gyms',
                'gyms as owned_gyms_count' => fn ($query) => $query->where('gym_user.role', GymRole::Owner->value),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($accountType === 'subscriber', fn ($query) => $query->whereHas('subscription'))
            ->when($accountType === 'staff', fn ($query) => $query
                ->whereDoesntHave('subscription')
                ->whereHas('gyms', fn ($query) => $query->whereIn('gym_user.role', [
                    GymRole::Admin->value,
                    GymRole::Trainer->value,
                ])))
            ->when($accountType === 'platform_admin', fn ($query) => $query->where('is_platform_admin', true))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($planId !== null, fn ($query) => $query->whereHas(
                'subscription',
                fn ($query) => $query->where('saas_plan_id', (int) $planId),
            ))
            ->when($billingStatus === 'pending', fn ($query) => $query->whereHas(
                'subscription.payments',
                fn ($query) => $query->where('status', SubscriptionPaymentStatus::Pending),
            ))
            ->latest('id')
            ->paginate((int) $request->validated('per_page', 15))
            ->withQueryString()
            ->through(fn (User $user): array => $this->userData($user));

        return Inertia::render('platform/users/index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'account_type' => is_string($accountType) ? $accountType : '',
                'status' => is_string($status) ? $status : '',
                'plan_id' => $planId === null ? '' : (string) $planId,
                'billing_status' => is_string($billingStatus) ? $billingStatus : '',
                'per_page' => (int) $request->validated('per_page', 15),
            ],
            'plans' => $this->planOptions(),
        ]);
    }

    public function show(User $user): Response
    {
        $user->load([
            'subscription.plan',
            'subscription.payments' => fn ($query) => $query
                ->with('reviewer:id,name')
                ->latest('id')
                ->limit(20),
            'gyms' => fn ($query) => $query
                ->select(['gyms.id', 'gyms.name', 'gyms.slug', 'gyms.status', 'gyms.created_at'])
                ->withCount([
                    'members',
                    'users as staff_count' => fn ($query) => $query
                        ->where('gym_user.role', '!=', GymRole::Owner->value),
                ])
                ->oldest('gym_user.created_at'),
        ])->loadCount([
            'gyms',
            'gyms as owned_gyms_count' => fn ($query) => $query->where('gym_user.role', GymRole::Owner->value),
        ]);

        $subscription = $user->subscription;

        return Inertia::render('platform/users/show', [
            'managedUser' => [
                ...$this->userData($user),
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'can_update_status' => ! $user->is_platform_admin,
                'gyms' => $user->gyms->map(fn ($gym): array => [
                    'id' => $gym->getKey(),
                    'name' => $gym->name,
                    'slug' => $gym->slug,
                    'status' => $gym->status->value,
                    'status_label' => $gym->status->label(),
                    'role' => (string) $gym->getRelation('pivot')->getAttribute('role'),
                    'role_label' => GymRole::from((string) $gym->getRelation('pivot')->getAttribute('role'))->label(),
                    'access_status' => (string) $gym->getRelation('pivot')->getAttribute('status'),
                    'members_count' => (int) $gym->getAttribute('members_count'),
                    'staff_count' => (int) $gym->getAttribute('staff_count'),
                ]),
                'subscription' => $subscription === null
                    ? null
                    : $this->subscriptionData($subscription, true),
            ],
            'plans' => $this->planOptions(),
            'subscriptionStatusOptions' => array_map(fn (SubscriptionStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ], SubscriptionStatus::cases()),
        ]);
    }

    /** @return array<string, mixed> */
    private function userData(User $user): array
    {
        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'is_platform_admin' => $user->is_platform_admin,
            'is_active' => $user->is_active,
            'account_type' => $user->is_platform_admin
                ? 'platform_admin'
                : ($user->subscription === null ? 'staff' : 'subscriber'),
            'account_type_label' => $user->is_platform_admin
                ? 'Platform Super Admin'
                : ($user->subscription === null ? 'Staf gym' : 'Subscriber / Owner'),
            'gyms_count' => (int) ($user->getAttribute('gyms_count') ?? 0),
            'owned_gyms_count' => (int) ($user->getAttribute('owned_gyms_count') ?? 0),
            'created_at' => $user->created_at?->toIso8601String(),
            'subscription' => $user->subscription === null
                ? null
                : $this->subscriptionData($user->subscription),
        ];
    }

    /** @return array<string, mixed> */
    private function subscriptionData(Subscription $subscription, bool $withUsage = false): array
    {
        $data = [
            'id' => $subscription->getKey(),
            'status' => $subscription->effectiveStatus()->value,
            'status_label' => $subscription->effectiveStatus()->label(),
            'plan_id' => $subscription->saas_plan_id,
            'plan_name' => $subscription->plan->name,
            'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
            'current_period_starts_at' => $subscription->current_period_starts_at?->toIso8601String(),
            'current_period_ends_at' => $subscription->current_period_ends_at?->toIso8601String(),
            'grants_access' => $subscription->grantsAccess(),
            'pending_payments_count' => $subscription->relationLoaded('payments')
                ? $subscription->payments
                    ->where('status', SubscriptionPaymentStatus::Pending)
                    ->count()
                : (int) ($subscription->getAttribute('pending_payments_count') ?? 0),
        ];

        if ($withUsage) {
            $data['usage'] = $this->subscriptionQuota->usage($subscription);
            $data['limits'] = [
                'gyms' => $subscription->plan->max_gyms,
                'members' => $subscription->plan->max_members,
                'staff' => $subscription->plan->max_staff,
            ];
            $data['payments'] = $subscription->payments
                ->map(fn (SubscriptionPayment $payment): array => [
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
                ])
                ->values();
        }

        return $data;
    }

    /** @return array<int, array<string, mixed>> */
    private function planOptions(): array
    {
        return SaasPlan::query()
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
            ])
            ->all();
    }
}
