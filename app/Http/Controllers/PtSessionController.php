<?php

namespace App\Http\Controllers;

use App\Actions\PersonalTraining\SchedulePtSession;
use App\Enums\GymRole;
use App\Enums\PtSessionStatus;
use App\Enums\TrainerStatus;
use App\Http\Requests\IndexPtSessionRequest;
use App\Http\Requests\StorePtSessionRequest;
use App\Models\MemberPtPackage;
use App\Models\PtSession;
use App\Models\Trainer;
use App\Models\User;
use App\Support\GymContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PtSessionController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function index(IndexPtSessionRequest $request): Response
    {
        $scope = (string) $request->validated('scope', 'today');
        $status = $request->validated('status');
        $date = $request->validated('date');
        $trainerId = $this->trainerFilter($request);
        $gym = $this->gymContext->gym();
        $now = CarbonImmutable::now($gym->timezone);
        $todayStartUtc = $now->startOfDay()->utc();
        $tomorrowStartUtc = $now->addDay()->startOfDay()->utc();
        $query = $gym->ptSessions()
            ->getQuery()
            ->when($trainerId !== null, fn (Builder $query) => $query->where('trainer_id', $trainerId))
            ->when(is_string($status), fn (Builder $query) => $query->where('status', $status))
            ->when($date === null && $scope === 'today', fn (Builder $query) => $query
                ->where('scheduled_at', '>=', $todayStartUtc)
                ->where('scheduled_at', '<', $tomorrowStartUtc))
            ->when($date === null && $scope === 'upcoming', fn (Builder $query) => $query
                ->where('status', PtSessionStatus::Scheduled->value)
                ->where('scheduled_at', '>=', $tomorrowStartUtc))
            ->when($date === null && $scope === 'history', fn (Builder $query) => $query
                ->where(function (Builder $query) use ($todayStartUtc): void {
                    $query->where('status', '!=', PtSessionStatus::Scheduled->value)
                        ->orWhere('scheduled_at', '<', $todayStartUtc);
                }))
            ->when(is_string($date), function (Builder $query) use ($date, $gym): void {
                $start = CarbonImmutable::parse($date, $gym->timezone)->startOfDay();
                $query->where('scheduled_at', '>=', $start->utc())
                    ->where('scheduled_at', '<', $start->addDay()->utc());
            });
        $sessions = $query
            ->select([
                'pt_sessions.id',
                'pt_sessions.member_pt_package_id',
                'pt_sessions.member_id',
                'pt_sessions.trainer_id',
                'pt_sessions.scheduled_at',
                'pt_sessions.duration_minutes',
                'pt_sessions.status',
                'pt_sessions.completed_at',
                'pt_sessions.notes',
                'pt_sessions.cancellation_reason',
                'pt_sessions.quota_consumed',
            ])
            ->with([
                'member:id,member_number,name,phone',
                'trainer:id,trainer_code,name,specialization',
                'memberPtPackage:id,pt_package_id,total_sessions,used_sessions,expires_at',
                'memberPtPackage.ptPackage:id,name',
            ])
            ->when(
                $scope === 'history',
                fn (Builder $query) => $query->latest('scheduled_at'),
                fn (Builder $query) => $query->oldest('scheduled_at'),
            )
            ->paginate((int) $request->validated('per_page', 15))
            ->withQueryString()
            ->through(fn (PtSession $session): array => $this->sessionData($session));

        return Inertia::render('pt-sessions/index', [
            'sessions' => $sessions,
            'filters' => [
                'scope' => $scope,
                'trainer_id' => $trainerId,
                'status' => is_string($status) ? $status : '',
                'date' => is_string($date) ? $date : '',
                'per_page' => (int) $request->validated('per_page', 15),
            ],
            'trainerOptions' => $this->trainerOptions(),
            'statusOptions' => $this->statusOptions(),
            'isTrainer' => $this->gymContext->role() === GymRole::Trainer,
        ]);
    }

    public function show(int $pt_session): Response
    {
        $session = $this->findSession($pt_session);
        Gate::authorize('view', $session);
        $scheduledCount = $session->memberPtPackage->sessions()
            ->where('status', PtSessionStatus::Scheduled->value)
            ->count();

        return Inertia::render('pt-sessions/show', [
            'session' => [
                ...$this->sessionData($session),
                'package' => $this->memberPtPackageData(
                    $session->memberPtPackage,
                    $scheduledCount,
                ),
            ],
            'canComplete' => Gate::allows('complete', $session),
            'canNoShow' => Gate::allows('markNoShow', $session),
            'canEdit' => Gate::allows('update', $session),
        ]);
    }

    public function store(
        StorePtSessionRequest $request,
        SchedulePtSession $schedulePtSession,
    ): RedirectResponse {
        $memberPtPackage = $this->gymContext->gym()->memberPtPackages()
            ->whereKey((int) $request->validated('member_pt_package_id'))
            ->firstOrFail();
        Gate::authorize('schedule', [PtSession::class, $memberPtPackage]);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $session = $schedulePtSession->handle(
            $memberPtPackage,
            $this->scheduledAtUtc($request),
            (int) $request->validated('duration_minutes'),
            is_string($request->validated('notes')) ? $request->validated('notes') : null,
            $user,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Sesi PT berhasil dijadwalkan.',
        ]);

        return to_route('pt-sessions.show', $session->getKey());
    }

    private function findSession(int $session): PtSession
    {
        return $this->gymContext->gym()->ptSessions()
            ->with([
                'member:id,member_number,name,phone',
                'trainer:id,trainer_code,name,specialization',
                'memberPtPackage:id,pt_package_id,total_sessions,used_sessions,start_date,expires_at,status',
                'memberPtPackage.ptPackage:id,name',
            ])
            ->whereKey($session)
            ->firstOrFail();
    }

    private function trainerFilter(IndexPtSessionRequest $request): ?int
    {
        if ($this->gymContext->role() === GymRole::Trainer) {
            return $this->currentTrainer($request->user()?->getKey())->getKey();
        }

        $trainerId = $request->validated('trainer_id');

        return is_numeric($trainerId)
            && $this->gymContext->gym()->trainers()->whereKey((int) $trainerId)->exists()
                ? (int) $trainerId
                : null;
    }

    private function currentTrainer(?int $userId): Trainer
    {
        return $this->gymContext->gym()->trainers()
            ->where('user_id', $userId)
            ->where('status', TrainerStatus::Active->value)
            ->firstOrFail();
    }

    private function scheduledAtUtc(StorePtSessionRequest $request): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            $request->validated('date').' '.$request->validated('start_time'),
            $this->gymContext->gym()->timezone,
        )->utc();
    }

    /** @return array<string, mixed> */
    private function sessionData(PtSession $session): array
    {
        return [
            'id' => $session->getKey(),
            'scheduled_at' => $session->scheduled_at->toIso8601String(),
            'duration_minutes' => $session->duration_minutes,
            'status' => $session->status->value,
            'status_label' => $session->status->label(),
            'completed_at' => $session->completed_at?->toIso8601String(),
            'notes' => $session->notes,
            'cancellation_reason' => $session->cancellation_reason,
            'quota_consumed' => $session->quota_consumed,
            'member' => [
                'id' => $session->member->getKey(),
                'member_number' => $session->member->member_number,
                'name' => $session->member->name,
                'phone' => $session->member->phone,
            ],
            'trainer' => [
                'id' => $session->trainer->getKey(),
                'trainer_code' => $session->trainer->trainer_code,
                'name' => $session->trainer->name,
                'specialization' => $session->trainer->specialization,
            ],
            'pt_package' => [
                'id' => $session->memberPtPackage->getKey(),
                'name' => $session->memberPtPackage->ptPackage->name,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function memberPtPackageData(
        MemberPtPackage $memberPtPackage,
        int $scheduledCount,
    ): array {
        return [
            'id' => $memberPtPackage->getKey(),
            'total_sessions' => $memberPtPackage->total_sessions,
            'used_sessions' => $memberPtPackage->used_sessions,
            'scheduled_sessions' => $scheduledCount,
            'available_sessions' => $memberPtPackage->availableSessions($scheduledCount),
            'start_date' => $memberPtPackage->start_date->toDateString(),
            'expires_at' => $memberPtPackage->expires_at?->toDateString(),
            'status' => $memberPtPackage->effectiveStatusOn(
                CarbonImmutable::now($this->gymContext->gym()->timezone)->startOfDay(),
            )->value,
        ];
    }

    /** @return array<int, array{value: int, label: string}> */
    private function trainerOptions(): array
    {
        if ($this->gymContext->role() === GymRole::Trainer) {
            return [];
        }

        return $this->gymContext->gym()->trainers()
            ->select(['trainers.id', 'trainers.name'])
            ->where('status', TrainerStatus::Active->value)
            ->orderBy('name')
            ->get()
            ->map(fn (Trainer $trainer): array => [
                'value' => $trainer->getKey(),
                'label' => $trainer->name,
            ])
            ->all();
    }

    /** @return array<int, array{value: string, label: string}> */
    private function statusOptions(): array
    {
        return array_map(
            fn (PtSessionStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            PtSessionStatus::cases(),
        );
    }
}
