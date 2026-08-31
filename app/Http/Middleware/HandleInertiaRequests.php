<?php

namespace App\Http\Middleware;

use App\Enums\GymUserStatus;
use App\Enums\SubscriptionPaymentStatus;
use App\Support\GymContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(private readonly GymContext $gymContext) {}

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => fn (): array => $this->authProps($request),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function authProps(Request $request): array
    {
        $currentGym = $this->gymContext->hasGym()
            ? $this->gymContext->gym()
            : null;
        $role = $this->gymContext->hasGym()
            ? $this->gymContext->role()
            : null;
        $subscription = $currentGym?->subscription()
            ->select([
                'id',
                'subscriber_id',
                'status',
                'trial_ends_at',
                'current_period_ends_at',
            ])
            ->first();

        return [
            'user' => $request->user(),
            'isPlatformAdmin' => $request->user()?->is_platform_admin === true,
            'currentGym' => $currentGym === null ? null : [
                'id' => $currentGym->getKey(),
                'name' => $currentGym->name,
                'slug' => $currentGym->slug,
                'logo_url' => $currentGym->logo === null
                    ? null
                    : route('gym-logo.show', ['v' => $currentGym->updated_at?->getTimestamp()]),
                'status' => $currentGym->status->value,
                'timezone' => $currentGym->timezone,
                'currency' => $currentGym->currency,
                'phone' => $currentGym->phone,
                'email' => $currentGym->email,
                'address' => $currentGym->address,
                'membership_expiry_warning_days' => $currentGym->membership_expiry_warning_days,
                'count_pt_no_show_as_used_session' => $currentGym->count_pt_no_show_as_used_session,
            ],
            'role' => $role?->value,
            'roleLabel' => $role?->label(),
            'permissions' => $role?->permissions() ?? [],
            'subscription' => $subscription === null ? null : [
                'status' => $subscription->effectiveStatus()->value,
                'status_label' => $subscription->effectiveStatus()->label(),
                'grants_access' => $subscription->grantsAccess(),
                'is_subscriber' => $subscription->subscriber_id === $request->user()?->getKey(),
                'has_pending_payment' => $subscription->payments()
                    ->where('status', SubscriptionPaymentStatus::Pending)
                    ->exists(),
            ],
            'availableGyms' => $request->user() === null ? [] : $request->user()->gyms()
                ->select(['gyms.id', 'gyms.name', 'gyms.status'])
                ->wherePivot('status', GymUserStatus::Active->value)
                ->oldest('gym_user.created_at')
                ->get()
                ->map(fn ($gym): array => [
                    'id' => $gym->getKey(),
                    'name' => $gym->name,
                    'status' => $gym->status->value,
                    'role' => (string) $gym->getRelation('pivot')->getAttribute('role'),
                ]),
        ];
    }
}
