<?php

namespace App\Support;

use App\Enums\MemberPtPackageStatus;
use App\Enums\MemberStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PtSessionStatus;
use App\Enums\TrainerStatus;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\Trainer;
use Carbon\CarbonImmutable;

class ReportSnapshot
{
    public function __construct(private readonly GymContext $gymContext) {}

    /** @return array<string, mixed> */
    public function make(ReportDateRange $range): array
    {
        return [
            'revenue' => $this->revenue($range),
            'members' => $this->members($range),
            'memberships' => $this->memberships($range),
            'check_ins' => $this->checkIns($range),
            'personal_training' => $this->personalTraining($range),
        ];
    }

    /** @return array<string, mixed> */
    private function revenue(ReportDateRange $range): array
    {
        $query = $this->gymContext->gym()->payments()
            ->where('status', PaymentStatus::Paid->value)
            ->where('paid_at', '>=', $range->startUtc())
            ->where('paid_at', '<', $range->endUtcExclusive());
        $summary = (clone $query)
            ->toBase()
            ->selectRaw(
                'COALESCE(SUM(amount), 0) AS total, COUNT(*) AS payment_count, '
                .'COALESCE(AVG(amount), 0) AS average, '
                .'COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) AS membership_total, '
                .'COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) AS pt_total',
                [
                    PaymentType::Membership->value,
                    PaymentType::PersonalTraining->value,
                ],
            )
            ->first();
        $total = $this->decimalString($summary->total ?? 0);
        $paymentCount = (int) ($summary->payment_count ?? 0);

        $methodBreakdown = (clone $query)
            ->toBase()
            ->select('method')
            ->selectRaw('COUNT(*) AS payment_count, COALESCE(SUM(amount), 0) AS total')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get()
            ->map(function (object $item): array {
                $method = is_string($item->method)
                    ? PaymentMethod::tryFrom($item->method)
                    : null;

                return [
                    'method' => $method?->value,
                    'label' => $method?->label() ?? 'Tanpa metode',
                    'payment_count' => (int) $item->payment_count,
                    'total' => $this->decimalString($item->total),
                ];
            })
            ->all();

        return [
            'total' => $total,
            'payment_count' => $paymentCount,
            'average' => $this->decimalString($summary->average ?? 0),
            'membership_total' => $this->decimalString($summary->membership_total ?? 0),
            'pt_total' => $this->decimalString($summary->pt_total ?? 0),
            'method_breakdown' => $methodBreakdown,
        ];
    }

    /** @return array<string, mixed> */
    private function personalTraining(ReportDateRange $range): array
    {
        $gym = $this->gymContext->gym();
        $asOf = $range->end->toDateString();
        $nowUtc = CarbonImmutable::now('UTC');
        $activeClients = $gym->memberPtPackages()
            ->where('status', MemberPtPackageStatus::Active->value)
            ->whereDate('start_date', '<=', $asOf)
            ->where(function ($query) use ($asOf): void {
                $query->whereNull('expires_at')->orWhereDate('expires_at', '>=', $asOf);
            })
            ->distinct()
            ->count('member_id');
        $packagesSold = $gym->memberPtPackages()
            ->where('created_at', '>=', $range->startUtc())
            ->where('created_at', '<', $range->endUtcExclusive())
            ->count();
        $sessionMetrics = $gym->ptSessions()
            ->toBase()
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN status = ? AND completed_at >= ? AND completed_at < ? THEN 1 ELSE 0 END), 0) AS completed_sessions, '
                .'COALESCE(SUM(CASE WHEN status = ? AND scheduled_at >= ? THEN 1 ELSE 0 END), 0) AS upcoming_sessions, '
                .'COALESCE(SUM(CASE WHEN status = ? AND scheduled_at >= ? AND scheduled_at < ? THEN 1 ELSE 0 END), 0) AS no_shows',
                [
                    PtSessionStatus::Completed->value,
                    $range->startUtc(),
                    $range->endUtcExclusive(),
                    PtSessionStatus::Scheduled->value,
                    $nowUtc,
                    PtSessionStatus::NoShow->value,
                    $range->startUtc(),
                    $range->endUtcExclusive(),
                ],
            )
            ->first();
        $revenue = $gym->payments()
            ->where('type', PaymentType::PersonalTraining->value)
            ->where('status', PaymentStatus::Paid->value)
            ->where('paid_at', '>=', $range->startUtc())
            ->where('paid_at', '<', $range->endUtcExclusive())
            ->sum('amount');
        $trainerSummary = $gym->trainers()
            ->select(['trainers.id', 'trainers.trainer_code', 'trainers.name'])
            ->where('status', TrainerStatus::Active->value)
            ->withCount([
                'ptPackages as active_clients_count' => fn ($query) => $query
                    ->where('status', MemberPtPackageStatus::Active->value)
                    ->whereDate('start_date', '<=', $asOf)
                    ->where(function ($query) use ($asOf): void {
                        $query->whereNull('expires_at')->orWhereDate('expires_at', '>=', $asOf);
                    }),
                'ptSessions as completed_sessions_count' => fn ($query) => $query
                    ->where('status', PtSessionStatus::Completed->value)
                    ->where('completed_at', '>=', $range->startUtc())
                    ->where('completed_at', '<', $range->endUtcExclusive()),
                'ptSessions as upcoming_sessions_count' => fn ($query) => $query
                    ->where('status', PtSessionStatus::Scheduled->value)
                    ->where('scheduled_at', '>=', $nowUtc),
            ])
            ->orderBy('name')
            ->limit(100)
            ->get()
            ->map(fn (Trainer $trainer): array => [
                'id' => $trainer->getKey(),
                'trainer_code' => $trainer->trainer_code,
                'name' => $trainer->name,
                'active_clients' => (int) $trainer->getAttribute('active_clients_count'),
                'completed_sessions' => (int) $trainer->getAttribute('completed_sessions_count'),
                'upcoming_sessions' => (int) $trainer->getAttribute('upcoming_sessions_count'),
            ])
            ->all();

        return [
            'active_clients' => $activeClients,
            'packages_sold' => $packagesSold,
            'revenue' => $this->decimalString($revenue),
            'completed_sessions' => (int) ($sessionMetrics->completed_sessions ?? 0),
            'upcoming_sessions' => (int) ($sessionMetrics->upcoming_sessions ?? 0),
            'no_shows' => (int) ($sessionMetrics->no_shows ?? 0),
            'trainers' => $trainerSummary,
        ];
    }

    /** @return array{active: int, inactive: int, new_in_period: int} */
    private function members(ReportDateRange $range): array
    {
        $summary = $this->gymContext->gym()->members()
            ->toBase()
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS active_members, '
                .'COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS inactive_members, '
                .'COALESCE(SUM(CASE WHEN created_at >= ? AND created_at < ? THEN 1 ELSE 0 END), 0) AS new_members',
                [
                    MemberStatus::Active->value,
                    MemberStatus::Inactive->value,
                    $range->startUtc(),
                    $range->endUtcExclusive(),
                ],
            )
            ->first();

        return [
            'active' => (int) ($summary->active_members ?? 0),
            'inactive' => (int) ($summary->inactive_members ?? 0),
            'new_in_period' => (int) ($summary->new_members ?? 0),
        ];
    }

    /** @return array{active: int, expired: int, expiring_soon: int, started_in_period: int, warning_days: int} */
    private function memberships(ReportDateRange $range): array
    {
        $gym = $this->gymContext->gym();
        $asOfDate = $range->end->toDateString();
        $warningEnd = $range->end
            ->addDays($gym->membership_expiry_warning_days)
            ->toDateString();
        $membershipTable = (new MemberMembership)->getTable();
        $metrics = $gym->memberMemberships()
            ->toBase()
            ->selectRaw(
                'COUNT(DISTINCT CASE WHEN start_date <= ? AND end_date >= ? THEN member_id END) AS active_memberships, '
                .'COUNT(DISTINCT CASE WHEN start_date <= ? AND end_date BETWEEN ? AND ? THEN member_id END) AS expiring_soon, '
                .'COALESCE(SUM(CASE WHEN start_date BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) AS started_in_period',
                [
                    $asOfDate,
                    $asOfDate,
                    $asOfDate,
                    $asOfDate,
                    $warningEnd,
                    $range->start->toDateString(),
                    $range->end->toDateString(),
                ],
            )
            ->first();

        $expired = $gym->memberMemberships()
            ->where('end_date', '<', $asOfDate)
            ->whereNotExists(function ($query) use ($asOfDate, $gym, $membershipTable): void {
                $query->selectRaw('1')
                    ->from($membershipTable.' as active_memberships')
                    ->whereColumn(
                        'active_memberships.member_id',
                        $membershipTable.'.member_id',
                    )
                    ->where('active_memberships.gym_id', $gym->getKey())
                    ->where('active_memberships.start_date', '<=', $asOfDate)
                    ->where('active_memberships.end_date', '>=', $asOfDate);
            })
            ->distinct()
            ->count('member_id');

        return [
            'active' => (int) ($metrics->active_memberships ?? 0),
            'expired' => $expired,
            'expiring_soon' => (int) ($metrics->expiring_soon ?? 0),
            'started_in_period' => (int) ($metrics->started_in_period ?? 0),
            'warning_days' => $gym->membership_expiry_warning_days,
        ];
    }

    /** @return array<string, mixed> */
    private function checkIns(ReportDateRange $range): array
    {
        $query = $this->gymContext->gym()->checkIns()
            ->where('checked_in_at', '>=', $range->startUtc())
            ->where('checked_in_at', '<', $range->endUtcExclusive());
        $summary = (clone $query)
            ->toBase()
            ->selectRaw('COUNT(*) AS total, COUNT(DISTINCT member_id) AS unique_members')
            ->first();
        $total = (int) ($summary->total ?? 0);
        $visits = (clone $query)
            ->toBase()
            ->select('member_id')
            ->selectRaw('COUNT(*) AS visit_count')
            ->groupBy('member_id')
            ->orderByDesc('visit_count')
            ->orderBy('member_id')
            ->limit(5)
            ->get();
        $members = $this->gymContext->gym()->members()
            ->select(['members.id', 'members.member_number', 'members.name'])
            ->whereKey($visits->pluck('member_id'))
            ->get()
            ->keyBy('id');

        return [
            'total' => $total,
            'unique_members' => (int) ($summary->unique_members ?? 0),
            'daily_average' => number_format($total / $range->days(), 1, '.', ''),
            'top_visitors' => $visits
                ->map(function (object $visit) use ($members): ?array {
                    $member = $members->get((int) $visit->member_id);

                    if (! $member instanceof Member) {
                        return null;
                    }

                    return [
                        'member' => [
                            'id' => $member->getKey(),
                            'member_number' => $member->member_number,
                            'name' => $member->name,
                        ],
                        'visit_count' => (int) $visit->visit_count,
                    ];
                })
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function decimalString(mixed $value): string
    {
        $decimal = (string) ($value ?? '0');
        [$whole, $fraction] = array_pad(explode('.', $decimal, 2), 2, '');

        return $whole.'.'.str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
