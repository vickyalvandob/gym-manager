<?php

namespace App\Support;

use App\Enums\GymRole;
use App\Enums\MemberPtPackageStatus;
use App\Enums\PaymentStatus;
use App\Enums\PtSessionStatus;
use App\Enums\TrainerStatus;
use App\Models\CheckIn;
use App\Models\MemberMembership;
use App\Models\MemberPtPackage;
use App\Models\Payment;
use App\Models\PtSession;
use App\Models\Trainer;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class DashboardSnapshot
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly Request $request,
    ) {}

    /** @return array<string, mixed> */
    public function make(): array
    {
        $gym = $this->gymContext->gym();
        $role = $this->gymContext->role();
        $today = CarbonImmutable::today($gym->timezone);
        $tomorrow = $today->addDay();
        $monthStart = $today->startOfMonth();
        $warningEnd = $today->addDays($gym->membership_expiry_warning_days);
        $membershipTable = (new MemberMembership)->getTable();

        if ($role === GymRole::Trainer) {
            return $this->trainerSnapshot($today, $tomorrow);
        }

        $membershipMetrics = $gym->memberMemberships()
            ->toBase()
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN start_date <= ? AND end_date >= ? THEN member_id END) AS active_members, '
                .'COUNT(DISTINCT CASE WHEN start_date <= ? AND end_date BETWEEN ? AND ? THEN member_id END) AS expiring_soon',
                [
                    $today->toDateString(),
                    $today->toDateString(),
                    $today->toDateString(),
                    $today->toDateString(),
                    $warningEnd->toDateString(),
                ],
            )
            ->first();

        $expiredMembers = $gym->memberMemberships()
            ->where('end_date', '<', $today->toDateString())
            ->whereNotExists(function ($query) use ($gym, $membershipTable, $today): void {
                $query->selectRaw('1')
                    ->from($membershipTable.' as current_memberships')
                    ->whereColumn(
                        'current_memberships.member_id',
                        $membershipTable.'.member_id',
                    )
                    ->where('current_memberships.gym_id', $gym->getKey())
                    ->where('current_memberships.start_date', '<=', $today->toDateString())
                    ->where('current_memberships.end_date', '>=', $today->toDateString());
            })
            ->distinct()
            ->count('member_id');

        $newMembersThisMonth = $gym->members()
            ->where('created_at', '>=', $monthStart->utc())
            ->where('created_at', '<', $tomorrow->utc())
            ->count();
        $checkInsToday = $gym->checkIns()
            ->where('checked_in_at', '>=', $today->utc())
            ->where('checked_in_at', '<', $tomorrow->utc())
            ->count();

        [$pendingPaymentsCount, $pendingPaymentsAmount] = $this->pendingPaymentMetrics();
        [$revenueToday, $revenueThisMonth, $revenueTrend] = $role === GymRole::Owner
            ? $this->ownerRevenueMetrics($monthStart, $today, $tomorrow)
            : [null, null, null];

        return [
            'metrics' => [
                'active_members' => (int) ($membershipMetrics->active_members ?? 0),
                'expired_members' => $expiredMembers,
                'expiring_soon' => (int) ($membershipMetrics->expiring_soon ?? 0),
                'new_members_this_month' => $newMembersThisMonth,
                'check_ins_today' => $checkInsToday,
                'revenue_today' => $revenueToday,
                'revenue_this_month' => $revenueThisMonth,
                'pending_payments_count' => $pendingPaymentsCount,
                'pending_payments_amount' => $pendingPaymentsAmount,
            ],
            'revenue_trend' => $revenueTrend,
            'recent_check_ins' => $this->recentCheckIns(),
            'recent_payments' => $this->recentPayments(),
            'trainer_workspace' => null,
            'generated_at' => CarbonImmutable::now('UTC')->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function trainerSnapshot(
        CarbonImmutable $today,
        CarbonImmutable $tomorrow,
    ): array {
        $todayDate = $today->toDateString();
        $trainer = $this->gymContext->gym()->trainers()
            ->select([
                'trainers.id',
                'trainers.user_id',
                'trainers.name',
                'trainers.specialization',
                'trainers.status',
            ])
            ->withCount([
                'members as assigned_members_count' => fn ($query) => $query
                    ->where('members.gym_id', $this->gymContext->gymId()),
                'ptPackages as active_pt_clients_count' => fn ($query) => $query
                    ->where('member_pt_packages.gym_id', $this->gymContext->gymId())
                    ->where('status', MemberPtPackageStatus::Active->value)
                    ->where('start_date', '<=', $todayDate)
                    ->where(function ($query) use ($todayDate): void {
                        $query->whereNull('expires_at')->orWhere('expires_at', '>=', $todayDate);
                    }),
                'ptSessions as upcoming_sessions_count' => fn ($query) => $query
                    ->where('pt_sessions.gym_id', $this->gymContext->gymId())
                    ->where('status', PtSessionStatus::Scheduled->value)
                    ->where('scheduled_at', '>=', $tomorrow->utc()),
            ])
            ->where('user_id', $this->request->user()?->getKey())
            ->where('status', TrainerStatus::Active->value)
            ->first();

        if (! $trainer instanceof Trainer) {
            return $this->emptyTrainerSnapshot();
        }

        $assignedMembersCount = (int) $trainer->getAttribute('assigned_members_count');
        $activePtClientsCount = (int) $trainer->getAttribute('active_pt_clients_count');
        $upcomingSessionsCount = (int) $trainer->getAttribute('upcoming_sessions_count');
        $todaySessions = $this->gymContext->gym()->ptSessions()
            ->select([
                'pt_sessions.id',
                'pt_sessions.member_pt_package_id',
                'pt_sessions.member_id',
                'pt_sessions.scheduled_at',
                'pt_sessions.duration_minutes',
                'pt_sessions.status',
            ])
            ->where('trainer_id', $trainer->getKey())
            ->where('scheduled_at', '>=', $today->utc())
            ->where('scheduled_at', '<', $tomorrow->utc())
            ->with([
                'member:id,member_number,name',
                'memberPtPackage.ptPackage:id,name',
            ])
            ->orderBy('scheduled_at')
            ->get();

        return [
            'metrics' => [
                'active_members' => $assignedMembersCount,
                'expired_members' => 0,
                'expiring_soon' => 0,
                'new_members_this_month' => 0,
                'check_ins_today' => 0,
                'revenue_today' => null,
                'revenue_this_month' => null,
                'pending_payments_count' => null,
                'pending_payments_amount' => null,
            ],
            'revenue_trend' => null,
            'recent_check_ins' => [],
            'recent_payments' => [],
            'trainer_workspace' => [
                'trainer' => [
                    'id' => $trainer->getKey(),
                    'name' => $trainer->name,
                    'specialization' => $trainer->specialization,
                ],
                'assigned_members_count' => $assignedMembersCount,
                'today_sessions_count' => $todaySessions->count(),
                'upcoming_sessions_count' => $upcomingSessionsCount,
                'active_pt_clients_count' => $activePtClientsCount,
                'today_sessions' => $todaySessions
                    ->map(fn (PtSession $session): array => [
                        'id' => $session->getKey(),
                        'scheduled_at' => $session->scheduled_at->toIso8601String(),
                        'duration_minutes' => $session->duration_minutes,
                        'status' => $session->status->value,
                        'status_label' => $session->status->label(),
                        'member' => [
                            'id' => $session->member->getKey(),
                            'member_number' => $session->member->member_number,
                            'name' => $session->member->name,
                        ],
                        'pt_package_name' => $session->memberPtPackage->ptPackage->name,
                    ])
                    ->all(),
                'session_members' => $this->trainerSessionMembers($trainer, $todayDate),
            ],
            'generated_at' => CarbonImmutable::now('UTC')->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function emptyTrainerSnapshot(): array
    {
        return [
            'metrics' => [
                'active_members' => 0,
                'expired_members' => 0,
                'expiring_soon' => 0,
                'new_members_this_month' => 0,
                'check_ins_today' => 0,
                'revenue_today' => null,
                'revenue_this_month' => null,
                'pending_payments_count' => null,
                'pending_payments_amount' => null,
            ],
            'revenue_trend' => null,
            'recent_check_ins' => [],
            'recent_payments' => [],
            'trainer_workspace' => [
                'trainer' => null,
                'assigned_members_count' => 0,
                'today_sessions_count' => 0,
                'upcoming_sessions_count' => 0,
                'active_pt_clients_count' => 0,
                'today_sessions' => [],
                'session_members' => [],
            ],
            'generated_at' => CarbonImmutable::now('UTC')->toIso8601String(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function trainerSessionMembers(Trainer $trainer, string $today): array
    {
        return $this->gymContext->gym()->memberPtPackages()
            ->select([
                'member_pt_packages.id',
                'member_pt_packages.member_id',
                'member_pt_packages.pt_package_id',
                'member_pt_packages.total_sessions',
                'member_pt_packages.used_sessions',
                'member_pt_packages.expires_at',
            ])
            ->where('trainer_id', $trainer->getKey())
            ->where('status', MemberPtPackageStatus::Active->value)
            ->where('start_date', '<=', $today)
            ->where(function ($query) use ($today): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', $today);
            })
            ->with([
                'member:id,member_number,name',
                'ptPackage:id,name',
            ])
            ->withCount([
                'sessions as scheduled_sessions_count' => fn ($query) => $query
                    ->where('status', PtSessionStatus::Scheduled->value),
            ])
            ->latest('member_pt_packages.id')
            ->limit(8)
            ->get()
            ->map(function (MemberPtPackage $memberPtPackage): array {
                $scheduledSessions = (int) $memberPtPackage->getAttribute('scheduled_sessions_count');

                return [
                    'id' => $memberPtPackage->getKey(),
                    'member' => [
                        'id' => $memberPtPackage->member->getKey(),
                        'member_number' => $memberPtPackage->member->member_number,
                        'name' => $memberPtPackage->member->name,
                    ],
                    'pt_package_name' => $memberPtPackage->ptPackage->name,
                    'remaining_sessions' => max(
                        0,
                        $memberPtPackage->total_sessions - $memberPtPackage->used_sessions,
                    ),
                    'available_sessions' => $memberPtPackage->availableSessions($scheduledSessions),
                    'expires_at' => $memberPtPackage->expires_at?->toDateString(),
                ];
            })
            ->all();
    }

    /** @return array{0: string, 1: string, 2: array<int, array{date: string, amount: string}>} */
    private function ownerRevenueMetrics(
        CarbonImmutable $monthStart,
        CarbonImmutable $today,
        CarbonImmutable $tomorrow,
    ): array {
        $trendStart = $today->subDays(6);
        $queryStart = $monthStart->lessThan($trendStart) ? $monthStart : $trendStart;
        $selects = [
            'COALESCE(SUM(CASE WHEN paid_at >= ? THEN amount ELSE 0 END), 0) AS revenue_today',
            'COALESCE(SUM(CASE WHEN paid_at >= ? THEN amount ELSE 0 END), 0) AS revenue_month',
        ];
        $bindings = [$today->utc(), $monthStart->utc()];
        /** @var array<int, array{date: string, alias: string}> $trendDays */
        $trendDays = [];

        for ($index = 0; $index < 7; $index++) {
            $dayStart = $trendStart->addDays($index);
            $dayEnd = $dayStart->addDay();
            $alias = 'revenue_day_'.$index;
            $selects[] = "COALESCE(SUM(CASE WHEN paid_at >= ? AND paid_at < ? THEN amount ELSE 0 END), 0) AS {$alias}";
            $bindings[] = $dayStart->utc();
            $bindings[] = $dayEnd->utc();
            $trendDays[] = [
                'date' => $dayStart->toDateString(),
                'alias' => $alias,
            ];
        }

        $metrics = $this->gymContext->gym()->payments()
            ->where('status', PaymentStatus::Paid->value)
            ->where('paid_at', '>=', $queryStart->utc())
            ->where('paid_at', '<', $tomorrow->utc())
            ->toBase()
            ->selectRaw(implode(', ', $selects), $bindings)
            ->first();
        $revenueTrend = [];

        foreach ($trendDays as $day) {
            $alias = $day['alias'];
            $revenueTrend[] = [
                'date' => $day['date'],
                'amount' => $this->decimalString($metrics->{$alias} ?? 0),
            ];
        }

        return [
            $this->decimalString($metrics->revenue_today ?? 0),
            $this->decimalString($metrics->revenue_month ?? 0),
            $revenueTrend,
        ];
    }

    /** @return array{0: int, 1: string} */
    private function pendingPaymentMetrics(): array
    {
        $metrics = $this->gymContext->gym()->payments()
            ->where('status', PaymentStatus::Pending->value)
            ->toBase()
            ->selectRaw('COUNT(*) AS payment_count, COALESCE(SUM(amount), 0) AS amount')
            ->first();

        return [
            (int) ($metrics->payment_count ?? 0),
            $this->decimalString($metrics->amount ?? 0),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function recentCheckIns(): array
    {
        return $this->gymContext->gym()->checkIns()
            ->select([
                'check_ins.id',
                'check_ins.member_id',
                'check_ins.member_membership_id',
                'check_ins.checked_in_at',
            ])
            ->with([
                'member:id,member_number,name',
                'memberMembership:id,plan_name',
            ])
            ->latest('check_ins.checked_in_at')
            ->latest('check_ins.id')
            ->limit(6)
            ->get()
            ->map(fn (CheckIn $checkIn): array => [
                'id' => $checkIn->getKey(),
                'checked_in_at' => $checkIn->checked_in_at->toIso8601String(),
                'member' => [
                    'id' => $checkIn->member->getKey(),
                    'member_number' => $checkIn->member->member_number,
                    'name' => $checkIn->member->name,
                ],
                'membership' => [
                    'plan_name' => $checkIn->memberMembership->plan_name,
                ],
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function recentPayments(): array
    {
        return $this->gymContext->gym()->payments()
            ->select([
                'payments.id',
                'payments.member_id',
                'payments.invoice_number',
                'payments.amount',
                'payments.method',
                'payments.status',
                'payments.paid_at',
                'payments.created_at',
            ])
            ->with('member:id,member_number,name')
            ->latest('payments.id')
            ->limit(6)
            ->get()
            ->map(fn (Payment $payment): array => [
                'id' => $payment->getKey(),
                'invoice_number' => $payment->invoice_number,
                'amount' => $payment->amount,
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
                'method_label' => $payment->method?->label(),
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'created_at' => $payment->created_at?->toIso8601String(),
                'member' => [
                    'id' => $payment->member->getKey(),
                    'member_number' => $payment->member->member_number,
                    'name' => $payment->member->name,
                ],
            ])
            ->all();
    }

    private function decimalString(mixed $value): string
    {
        $decimal = (string) ($value ?? '0');
        [$whole, $fraction] = array_pad(explode('.', $decimal, 2), 2, '');

        return $whole.'.'.str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
