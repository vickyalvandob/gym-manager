<?php

namespace App\Http\Controllers;

use App\Actions\CheckIns\RecordMemberCheckIn;
use App\Http\Requests\IndexCheckInRequest;
use App\Http\Requests\StoreCheckInRequest;
use App\Models\CheckIn;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\User;
use App\Support\CheckInData;
use App\Support\CheckInEligibility;
use App\Support\GymContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CheckInController extends Controller
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly CheckInEligibility $checkInEligibility,
        private readonly CheckInData $checkInData,
    ) {}

    public function index(IndexCheckInRequest $request): Response
    {
        $memberSearch = Str::squish((string) $request->validated('member_search', ''));
        $historySearch = Str::squish((string) $request->validated('history_search', ''));
        $dateFrom = $request->validated('date_from');
        $dateTo = $request->validated('date_to');
        $perPage = (int) $request->validated('per_page', 15);
        $gym = $this->gymContext->gym();
        $timezone = $gym->timezone;
        $now = CarbonImmutable::now('UTC');
        $today = $now->setTimezone($timezone)->startOfDay();
        $todayStartUtc = $today->utc();
        $tomorrowStartUtc = $today->addDay()->utc();
        $dateFromUtc = is_string($dateFrom)
            ? CarbonImmutable::parse($dateFrom, $timezone)->startOfDay()->utc()
            : null;
        $dateToUtc = is_string($dateTo)
            ? CarbonImmutable::parse($dateTo, $timezone)->addDay()->startOfDay()->utc()
            : null;

        $historyQuery = $this->filteredHistoryQuery(
            $historySearch,
            $dateFromUtc,
            $dateToUtc,
        );
        $history = $historyQuery
            ->select($this->checkInColumns())
            ->with($this->checkInRelations())
            ->latest('check_ins.checked_in_at')
            ->latest('check_ins.id')
            ->paginate($perPage, ['*'], 'history_page')
            ->withQueryString()
            ->through(fn (CheckIn $checkIn): array => $this->checkInData->make($checkIn));

        $recentCheckIns = $gym->checkIns()
            ->select($this->checkInColumns())
            ->with($this->checkInRelations())
            ->latest('check_ins.checked_in_at')
            ->latest('check_ins.id')
            ->limit(8)
            ->get()
            ->map(fn (CheckIn $checkIn): array => $this->checkInData->make($checkIn))
            ->all();

        return Inertia::render('check-ins/index', [
            'memberSearchResults' => $this->memberSearchResults($memberSearch, $today, $now),
            'recentCheckIns' => $recentCheckIns,
            'history' => $history,
            'todayCount' => $gym->checkIns()
                ->where('checked_in_at', '>=', $todayStartUtc)
                ->where('checked_in_at', '<', $tomorrowStartUtc)
                ->count(),
            'filters' => [
                'member_search' => $memberSearch,
                'history_search' => $historySearch,
                'date_from' => is_string($dateFrom) ? $dateFrom : '',
                'date_to' => is_string($dateTo) ? $dateTo : '',
                'per_page' => $perPage,
            ],
            'duplicateWindowMinutes' => CheckInEligibility::DuplicateWindowMinutes,
        ]);
    }

    public function store(
        StoreCheckInRequest $request,
        int $member,
        RecordMemberCheckIn $recordMemberCheckIn,
    ): RedirectResponse {
        $memberModel = $this->gymContext->gym()->members()
            ->whereKey($member)
            ->firstOrFail();
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $checkIn = $recordMemberCheckIn->handle($memberModel, $user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Check-in {$memberModel->member_number} berhasil dicatat.",
        ]);

        return back();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function memberSearchResults(
        string $search,
        CarbonImmutable $today,
        CarbonImmutable $now,
    ): array {
        if ($search === '') {
            return [];
        }

        return $this->gymContext->gym()->members()
            ->select([
                'members.id',
                'members.member_number',
                'members.name',
                'members.phone',
                'members.photo',
                'members.status',
            ])
            ->with([
                'memberships' => fn ($query) => $query
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
                    ->latest('id'),
                'latestCheckIn' => fn ($query) => $query
                    ->select([
                        'check_ins.id',
                        'check_ins.member_id',
                        'check_ins.checked_in_at',
                    ])
                    ->where('check_ins.gym_id', $this->gymContext->gymId()),
            ])
            ->where(function (Builder $query) use ($search): void {
                $query->where('member_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->orderBy('members.name')
            ->limit(8)
            ->get()
            ->map(function (Member $member) use ($now): array {
                $activeMembership = $member->memberships->first();
                $latestCheckIn = $member->latestCheckIn;

                return [
                    'id' => $member->getKey(),
                    'member_number' => $member->member_number,
                    'name' => $member->name,
                    'phone' => $member->phone,
                    'photo_url' => is_string($member->photo)
                        ? route('members.photo', $member->getKey())
                        : null,
                    'status' => $member->status->value,
                    'status_label' => $member->status->label(),
                    'membership' => $activeMembership instanceof MemberMembership
                        ? [
                            'id' => $activeMembership->getKey(),
                            'plan_name' => $activeMembership->plan_name,
                            'end_date' => $activeMembership->end_date->toDateString(),
                        ]
                        : null,
                    'eligibility' => $this->checkInEligibility->evaluate(
                        $member,
                        $activeMembership instanceof MemberMembership
                            ? $activeMembership
                            : null,
                        $latestCheckIn instanceof CheckIn ? $latestCheckIn : null,
                        $now,
                    ),
                ];
            })
            ->all();
    }

    /** @return Builder<CheckIn> */
    private function filteredHistoryQuery(
        string $search,
        ?CarbonImmutable $dateFromUtc,
        ?CarbonImmutable $dateToUtc,
    ): Builder {
        return $this->gymContext->gym()->checkIns()
            ->getQuery()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('member', function (Builder $query) use ($search): void {
                    $query->where(function (Builder $query) use ($search): void {
                        $query->where('member_number', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
                });
            })
            ->when(
                $dateFromUtc !== null,
                fn (Builder $query) => $query->where('check_ins.checked_in_at', '>=', $dateFromUtc),
            )
            ->when(
                $dateToUtc !== null,
                fn (Builder $query) => $query->where('check_ins.checked_in_at', '<', $dateToUtc),
            );
    }

    /** @return array<int, string> */
    private function checkInColumns(): array
    {
        return [
            'check_ins.id',
            'check_ins.member_id',
            'check_ins.member_membership_id',
            'check_ins.checked_in_at',
            'check_ins.created_by',
        ];
    }

    /** @return array<int, string> */
    private function checkInRelations(): array
    {
        return [
            'member:id,member_number,name,phone,photo',
            'memberMembership:id,plan_name,end_date',
            'createdBy:id,name',
        ];
    }
}
