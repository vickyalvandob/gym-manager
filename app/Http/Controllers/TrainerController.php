<?php

namespace App\Http\Controllers;

use App\Actions\Trainers\CreateTrainer;
use App\Actions\Trainers\UpdateTrainer;
use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\MemberPtPackageStatus;
use App\Enums\MemberStatus;
use App\Enums\PtSessionStatus;
use App\Enums\TrainerStatus;
use App\Http\Requests\IndexTrainerRequest;
use App\Http\Requests\ShowTrainerRequest;
use App\Http\Requests\StoreTrainerRequest;
use App\Http\Requests\UpdateTrainerRequest;
use App\Models\Member;
use App\Models\Trainer;
use App\Models\User;
use App\Support\GymContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TrainerController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function index(IndexTrainerRequest $request): Response
    {
        $search = Str::squish((string) $request->validated('search', ''));
        $status = $request->validated('status');

        $trainers = $this->gymContext->gym()->trainers()
            ->select([
                'trainers.id',
                'trainers.user_id',
                'trainers.trainer_code',
                'trainers.name',
                'trainers.phone',
                'trainers.email',
                'trainers.specialization',
                'trainers.bio',
                'trainers.status',
                'trainers.joined_at',
                'trainers.created_at',
            ])
            ->with('user:id,name,email')
            ->withCount('members')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('specialization', 'like', "%{$search}%");
                });
            })
            ->when(is_string($status), fn ($query) => $query->where('status', $status))
            ->latest('trainers.id')
            ->paginate((int) $request->validated('per_page', 15))
            ->withQueryString()
            ->through(fn (Trainer $trainer): array => $this->trainerData($trainer));

        return Inertia::render('trainers/index', [
            'trainers' => $trainers,
            'filters' => [
                'search' => $search,
                'status' => is_string($status) ? $status : '',
                'per_page' => (int) $request->validated('per_page', 15),
            ],
            'canCreate' => Gate::allows('create', Trainer::class),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Trainer::class);

        return Inertia::render('trainers/create', [
            'statusOptions' => $this->statusOptions(),
            'accountOptions' => $this->availableTrainerAccounts(),
        ]);
    }

    public function store(
        StoreTrainerRequest $request,
        CreateTrainer $createTrainer,
    ): RedirectResponse {
        $trainer = $createTrainer->handle($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Trainer {$trainer->name} berhasil ditambahkan.",
        ]);

        return to_route('trainers.show', $trainer->getKey());
    }

    public function show(ShowTrainerRequest $request, int $trainer): Response
    {
        $trainerModel = $this->findTrainer($trainer);
        Gate::authorize('view', $trainerModel);
        $memberSearch = Str::squish((string) $request->validated('member_search', ''));
        $today = CarbonImmutable::today($this->gymContext->gym()->timezone);
        $canAssign = Gate::allows('assignMembers', $trainerModel);
        $tomorrow = $today->addDay();

        $assignedMembers = $trainerModel->members()
            ->select([
                'members.id',
                'members.member_number',
                'members.name',
                'members.phone',
                'members.email',
                'members.status',
            ])
            ->with(['memberships' => fn ($query) => $query
                ->select([
                    'member_memberships.id',
                    'member_memberships.member_id',
                    'member_memberships.plan_name',
                    'member_memberships.start_date',
                    'member_memberships.end_date',
                ])
                ->where('member_memberships.gym_id', $this->gymContext->gymId())
                ->whereDate('start_date', '<=', $today->toDateString())
                ->whereDate('end_date', '>=', $today->toDateString())
                ->latest('start_date')
                ->latest('id')])
            ->orderByPivot('created_at', 'desc')
            ->paginate(
                (int) $request->validated('per_page', 15),
                ['*'],
                'member_page',
            )
            ->withQueryString()
            ->through(fn (Member $member): array => $this->assignedMemberData($member));

        return Inertia::render('trainers/show', [
            'trainer' => $this->trainerData($trainerModel),
            'assignedMembers' => $assignedMembers,
            'memberSearch' => $memberSearch,
            'assignableMembers' => $canAssign
                ? $this->assignableMembers($trainerModel, $memberSearch)
                : [],
            'canEdit' => Gate::allows('update', $trainerModel),
            'canAssign' => $canAssign,
            'stats' => [
                'assigned_members' => (int) ($trainerModel->getAttribute('members_count') ?? 0),
                'active_pt_clients' => $this->gymContext->gym()->memberPtPackages()
                    ->where('trainer_id', $trainerModel->getKey())
                    ->where('status', MemberPtPackageStatus::Active->value)
                    ->where(function ($query) use ($today): void {
                        $query->whereNull('expires_at')
                            ->orWhereDate('expires_at', '>=', $today->toDateString());
                    })
                    ->distinct()
                    ->count('member_id'),
                'sessions_today' => $this->gymContext->gym()->ptSessions()
                    ->where('trainer_id', $trainerModel->getKey())
                    ->where('status', PtSessionStatus::Scheduled->value)
                    ->where('scheduled_at', '>=', $today->utc())
                    ->where('scheduled_at', '<', $tomorrow->utc())
                    ->count(),
            ],
        ]);
    }

    public function edit(int $trainer): Response
    {
        $trainerModel = $this->findTrainer($trainer);
        Gate::authorize('update', $trainerModel);

        return Inertia::render('trainers/edit', [
            'trainer' => $this->trainerData($trainerModel),
            'statusOptions' => $this->statusOptions(),
            'accountOptions' => $this->availableTrainerAccounts($trainerModel),
        ]);
    }

    public function update(
        UpdateTrainerRequest $request,
        int $trainer,
        UpdateTrainer $updateTrainer,
    ): RedirectResponse {
        $trainerModel = $this->findTrainer($trainer);
        Gate::authorize('update', $trainerModel);
        $trainerModel = $updateTrainer->handle($trainerModel, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Trainer {$trainerModel->name} berhasil diperbarui.",
        ]);

        return to_route('trainers.show', $trainerModel->getKey());
    }

    private function findTrainer(int $trainer): Trainer
    {
        return $this->gymContext->gym()->trainers()
            ->with('user:id,name,email')
            ->withCount('members')
            ->whereKey($trainer)
            ->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function trainerData(Trainer $trainer): array
    {
        return [
            'id' => $trainer->getKey(),
            'user_id' => $trainer->user_id,
            'trainer_code' => $trainer->trainer_code,
            'name' => $trainer->name,
            'phone' => $trainer->phone,
            'email' => $trainer->email,
            'specialization' => $trainer->specialization,
            'bio' => $trainer->bio,
            'status' => $trainer->status->value,
            'status_label' => $trainer->status->label(),
            'joined_at' => $trainer->joined_at?->toDateString(),
            'notes' => $trainer->notes,
            'members_count' => (int) ($trainer->getAttribute('members_count') ?? 0),
            'linked_user' => $trainer->user === null ? null : [
                'id' => $trainer->user->getKey(),
                'name' => $trainer->user->name,
                'email' => $trainer->user->email,
            ],
            'created_at' => $trainer->created_at?->toIso8601String(),
            'updated_at' => $trainer->updated_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function assignedMemberData(Member $member): array
    {
        $membership = $member->memberships->first();
        $pivot = $member->getRelation('pivot');
        $assignedAt = $pivot->getAttribute('assigned_at')
            ?? $pivot->getAttribute('created_at');

        return [
            'id' => $member->getKey(),
            'member_number' => $member->member_number,
            'name' => $member->name,
            'phone' => $member->phone,
            'email' => $member->email,
            'status' => $member->status->value,
            'status_label' => $member->status->label(),
            'assigned_at' => $assignedAt === null
                ? null
                : CarbonImmutable::parse((string) $assignedAt)->toIso8601String(),
            'membership' => $membership === null ? null : [
                'plan_name' => $membership->plan_name,
                'end_date' => $membership->end_date->toDateString(),
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function assignableMembers(Trainer $trainer, string $search): array
    {
        return $this->gymContext->gym()->members()
            ->select([
                'members.id',
                'members.member_number',
                'members.name',
                'members.phone',
                'members.status',
            ])
            ->where('members.status', MemberStatus::Active->value)
            ->whereDoesntHave(
                'trainers',
                fn ($query) => $query->whereKey($trainer->getKey()),
            )
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('member_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest('members.id')
            ->limit(12)
            ->get()
            ->map(fn (Member $member): array => [
                'id' => $member->getKey(),
                'member_number' => $member->member_number,
                'name' => $member->name,
                'phone' => $member->phone,
                'status' => $member->status->value,
                'status_label' => $member->status->label(),
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array{value: int, label: string, description: string}> */
    private function availableTrainerAccounts(?Trainer $trainer = null): array
    {
        $usedUserIds = $this->gymContext->gym()->trainers()
            ->whereNotNull('user_id')
            ->when(
                $trainer !== null,
                fn ($query) => $query->where('trainers.id', '!=', $trainer->getKey()),
            )
            ->select('user_id');

        return $this->gymContext->gym()->users()
            ->select(['users.id', 'users.name', 'users.email'])
            ->wherePivot('role', GymRole::Trainer->value)
            ->wherePivot('status', GymUserStatus::Active->value)
            ->whereNotIn('users.id', $usedUserIds)
            ->orderBy('users.name')
            ->get()
            ->map(fn (User $user): array => [
                'value' => $user->getKey(),
                'label' => $user->name,
                'description' => $user->email,
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array{value: string, label: string}> */
    private function statusOptions(): array
    {
        return array_map(
            fn (TrainerStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            TrainerStatus::cases(),
        );
    }
}
