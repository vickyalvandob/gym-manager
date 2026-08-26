<?php

namespace App\Http\Controllers;

use App\Enums\MemberPtPackageStatus;
use App\Enums\PtSessionStatus;
use App\Enums\TrainerStatus;
use App\Http\Requests\IndexTrainerMemberRequest;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MemberPtPackage;
use App\Models\PtSession;
use App\Models\Trainer;
use App\Support\GymContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TrainerMemberController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function index(IndexTrainerMemberRequest $request): Response
    {
        $trainer = $this->currentTrainer($request->user()?->getKey());
        $search = Str::squish((string) $request->validated('search', ''));
        $today = CarbonImmutable::now($this->gymContext->gym()->timezone)->startOfDay();
        $members = $trainer->members()
            ->select([
                'members.id',
                'members.member_number',
                'members.name',
                'members.phone',
                'members.status',
            ])
            ->with($this->memberRelations($trainer, $today))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('member_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('members.name')
            ->paginate((int) $request->validated('per_page', 15))
            ->withQueryString()
            ->through(fn (Member $member): array => $this->memberData($member, $today));

        return Inertia::render('trainer-members/index', [
            'members' => $members,
            'filters' => [
                'search' => $search,
                'per_page' => (int) $request->validated('per_page', 15),
            ],
            'scheduleDate' => $today->addDay()->toDateString(),
        ]);
    }

    public function show(Request $request, int $member): Response
    {
        $trainer = $this->currentTrainer($request->user()?->getKey());
        $today = CarbonImmutable::now($this->gymContext->gym()->timezone)->startOfDay();
        $memberModel = $trainer->members()
            ->select([
                'members.id',
                'members.member_number',
                'members.name',
                'members.phone',
                'members.status',
            ])
            ->with($this->memberRelations($trainer, $today, true))
            ->whereKey($member)
            ->firstOrFail();

        return Inertia::render('trainer-members/show', [
            'member' => $this->memberData($memberModel, $today, true),
            'scheduleDate' => $today->addDay()->toDateString(),
        ]);
    }

    private function currentTrainer(?int $userId): Trainer
    {
        return $this->gymContext->gym()->trainers()
            ->where('user_id', $userId)
            ->where('status', TrainerStatus::Active->value)
            ->firstOrFail();
    }

    /** @return array<string, callable> */
    private function memberRelations(
        Trainer $trainer,
        CarbonImmutable $today,
        bool $includeHistory = false,
    ): array {
        $relations = [
            'memberships' => fn ($query) => $query
                ->select([
                    'member_memberships.id',
                    'member_memberships.member_id',
                    'member_memberships.plan_name',
                    'member_memberships.end_date',
                ])
                ->where('member_memberships.gym_id', $this->gymContext->gymId())
                ->whereDate('start_date', '<=', $today->toDateString())
                ->whereDate('end_date', '>=', $today->toDateString())
                ->latest('start_date')
                ->latest('id'),
            'ptPackages' => fn ($query) => $query
                ->select([
                    'member_pt_packages.id',
                    'member_pt_packages.member_id',
                    'member_pt_packages.pt_package_id',
                    'member_pt_packages.total_sessions',
                    'member_pt_packages.used_sessions',
                    'member_pt_packages.start_date',
                    'member_pt_packages.expires_at',
                    'member_pt_packages.status',
                ])
                ->where('member_pt_packages.gym_id', $this->gymContext->gymId())
                ->where('trainer_id', $trainer->getKey())
                ->whereIn('status', [
                    MemberPtPackageStatus::Active->value,
                    MemberPtPackageStatus::Completed->value,
                ])
                ->with('ptPackage:id,name')
                ->withCount([
                    'sessions as scheduled_sessions_count' => fn ($query) => $query
                        ->where('status', PtSessionStatus::Scheduled->value),
                ])
                ->latest('id'),
            'ptSessions' => fn ($query) => $query
                ->select([
                    'pt_sessions.id',
                    'pt_sessions.member_id',
                    'pt_sessions.member_pt_package_id',
                    'pt_sessions.scheduled_at',
                    'pt_sessions.duration_minutes',
                    'pt_sessions.status',
                    'pt_sessions.notes',
                ])
                ->where('pt_sessions.gym_id', $this->gymContext->gymId())
                ->where('trainer_id', $trainer->getKey())
                ->when(
                    ! $includeHistory,
                    fn ($query) => $query
                        ->where('status', PtSessionStatus::Scheduled->value)
                        ->where('scheduled_at', '>=', now()),
                )
                ->with('memberPtPackage.ptPackage:id,name')
                ->when(
                    $includeHistory,
                    fn ($query) => $query->orderByDesc('scheduled_at'),
                    fn ($query) => $query->orderBy('scheduled_at'),
                )
                ->limit($includeHistory ? 12 : 1),
        ];

        return $relations;
    }

    /** @return array<string, mixed> */
    private function memberData(
        Member $member,
        CarbonImmutable $today,
        bool $includeHistory = false,
    ): array {
        $membership = $member->memberships->first();
        $memberPtPackage = $member->ptPackages->first();
        $scheduledCount = (int) ($memberPtPackage?->getAttribute('scheduled_sessions_count') ?? 0);
        $sessions = $member->ptSessions
            ->map(fn (PtSession $session): array => [
                'id' => $session->getKey(),
                'scheduled_at' => $session->scheduled_at->toIso8601String(),
                'duration_minutes' => $session->duration_minutes,
                'status' => $session->status->value,
                'status_label' => $session->status->label(),
                'notes' => $session->notes,
                'pt_package_name' => $session->memberPtPackage->ptPackage->name,
            ])
            ->values()
            ->all();

        return [
            'id' => $member->getKey(),
            'member_number' => $member->member_number,
            'name' => $member->name,
            'phone' => $member->phone,
            'status' => $member->status->value,
            'status_label' => $member->status->label(),
            'membership' => $membership instanceof MemberMembership ? [
                'plan_name' => $membership->plan_name,
                'end_date' => $membership->end_date->toDateString(),
            ] : null,
            'pt_package' => $memberPtPackage instanceof MemberPtPackage ? [
                'id' => $memberPtPackage->getKey(),
                'name' => $memberPtPackage->ptPackage->name,
                'total_sessions' => $memberPtPackage->total_sessions,
                'used_sessions' => $memberPtPackage->used_sessions,
                'scheduled_sessions' => $scheduledCount,
                'available_sessions' => $memberPtPackage->availableSessions($scheduledCount),
                'expires_at' => $memberPtPackage->expires_at?->toDateString(),
                'status' => $memberPtPackage->effectiveStatusOn($today)->value,
            ] : null,
            'next_session' => $includeHistory ? null : ($sessions[0] ?? null),
            'sessions' => $includeHistory ? $sessions : [],
        ];
    }
}
