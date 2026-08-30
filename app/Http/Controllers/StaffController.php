<?php

namespace App\Http\Controllers;

use App\Actions\Staff\CreateFrontDesk;
use App\Actions\Staff\UpdateFrontDesk;
use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Http\Requests\IndexStaffRequest;
use App\Http\Requests\StoreFrontDeskRequest;
use App\Http\Requests\UpdateFrontDeskRequest;
use App\Models\Subscription;
use App\Models\User;
use App\Support\GymContext;
use App\Support\SubscriptionQuota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly SubscriptionQuota $subscriptionQuota,
    ) {}

    public function index(IndexStaffRequest $request): Response
    {
        $search = Str::squish((string) $request->validated('search', ''));
        $role = $request->validated('role');
        $status = $request->validated('status');
        $gym = $this->gymContext->gym();
        $staff = $gym->users()
            ->select(['users.id', 'users.name', 'users.email', 'users.is_active', 'users.created_at'])
            ->wherePivot('role', '!=', GymRole::Owner->value)
            ->with(['trainerProfiles' => fn ($query) => $query
                ->select(['id', 'gym_id', 'user_id', 'trainer_code', 'status'])
                ->where('gym_id', $gym->getKey())])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%");
                });
            })
            ->when(is_string($role), fn ($query) => $query->where('gym_user.role', $role))
            ->when(is_string($status), fn ($query) => $query->where('gym_user.status', $status))
            ->latest('gym_user.created_at')
            ->paginate((int) $request->validated('per_page', 15))
            ->withQueryString()
            ->through(fn (User $user): array => $this->staffData($user));
        $subscription = $gym->subscription()->with('plan')->first();

        return Inertia::render('staff/index', [
            'staff' => $staff,
            'filters' => [
                'search' => $search,
                'role' => is_string($role) ? $role : '',
                'status' => is_string($status) ? $status : '',
                'per_page' => (int) $request->validated('per_page', 15),
            ],
            'quota' => $subscription instanceof Subscription
                ? [
                    'used' => $this->subscriptionQuota->usage($subscription)['staff'],
                    'limit' => $subscription->plan->max_staff,
                    'plan_name' => $subscription->plan->name,
                ]
                : null,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('manageUsers', $this->gymContext->gym());

        return Inertia::render('staff/create', [
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(
        StoreFrontDeskRequest $request,
        CreateFrontDesk $createFrontDesk,
    ): RedirectResponse {
        $frontDesk = $createFrontDesk->handle($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Front Desk {$frontDesk->name} berhasil ditambahkan.",
        ]);

        return to_route('staff.index');
    }

    public function edit(int $user): Response
    {
        Gate::authorize('manageUsers', $this->gymContext->gym());
        $frontDesk = $this->findFrontDesk($user);

        return Inertia::render('staff/edit', [
            'staffMember' => $this->staffData($frontDesk),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(
        UpdateFrontDeskRequest $request,
        int $user,
        UpdateFrontDesk $updateFrontDesk,
    ): RedirectResponse {
        $frontDesk = $this->findFrontDesk($user);
        $frontDesk = $updateFrontDesk->handle($frontDesk, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Front Desk {$frontDesk->name} berhasil diperbarui.",
        ]);

        return to_route('staff.index');
    }

    private function findFrontDesk(int $user): User
    {
        return $this->gymContext->gym()->users()
            ->select(['users.id', 'users.name', 'users.email', 'users.is_active', 'users.created_at'])
            ->whereKey($user)
            ->wherePivot('role', GymRole::Admin->value)
            ->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function staffData(User $user): array
    {
        $pivot = $user->getRelation('pivot');
        $role = GymRole::from((string) $pivot->getAttribute('role'));
        $status = GymUserStatus::from((string) $pivot->getAttribute('status'));
        $trainer = $role === GymRole::Trainer ? $user->trainerProfiles->first() : null;

        return [
            'id' => $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role->value,
            'role_label' => $role->label(),
            'status' => $status->value,
            'status_label' => $status === GymUserStatus::Active ? 'Aktif' : 'Nonaktif',
            'is_account_active' => $user->is_active,
            'trainer_id' => $trainer?->getKey(),
            'trainer_code' => $trainer?->trainer_code,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function statusOptions(): array
    {
        return [
            ['value' => GymUserStatus::Active->value, 'label' => 'Aktif'],
            ['value' => GymUserStatus::Inactive->value, 'label' => 'Nonaktif'],
        ];
    }
}
