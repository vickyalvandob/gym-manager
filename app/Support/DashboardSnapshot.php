<?php

namespace App\Support;

use App\Enums\GymRole;
use App\Enums\PaymentStatus;
use App\Models\CheckIn;
use App\Models\MemberMembership;
use App\Models\Payment;
use Carbon\CarbonImmutable;

class DashboardSnapshot
{
    public function __construct(private readonly GymContext $gymContext) {}

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

        [$revenueToday, $revenueThisMonth] = $role === GymRole::Owner
            ? $this->revenueMetrics($monthStart->utc(), $today->utc(), $tomorrow->utc())
            : [null, null];

        $canOperateFrontDesk = in_array($role, [GymRole::Owner, GymRole::Admin], true);

        return [
            'metrics' => [
                'active_members' => (int) ($membershipMetrics->active_members ?? 0),
                'expired_members' => $expiredMembers,
                'expiring_soon' => (int) ($membershipMetrics->expiring_soon ?? 0),
                'new_members_this_month' => $newMembersThisMonth,
                'check_ins_today' => $checkInsToday,
                'revenue_today' => $revenueToday,
                'revenue_this_month' => $revenueThisMonth,
            ],
            'recent_check_ins' => $canOperateFrontDesk ? $this->recentCheckIns() : [],
            'recent_payments' => $canOperateFrontDesk ? $this->recentPayments() : [],
            'generated_at' => CarbonImmutable::now('UTC')->toIso8601String(),
        ];
    }

    /** @return array{0: string, 1: string} */
    private function revenueMetrics(
        CarbonImmutable $monthStartUtc,
        CarbonImmutable $todayStartUtc,
        CarbonImmutable $tomorrowStartUtc,
    ): array {
        $metrics = $this->gymContext->gym()->payments()
            ->where('status', PaymentStatus::Paid->value)
            ->where('paid_at', '>=', $monthStartUtc)
            ->where('paid_at', '<', $tomorrowStartUtc)
            ->toBase()
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN paid_at >= ? THEN amount ELSE 0 END), 0) AS revenue_today, '
                .'COALESCE(SUM(amount), 0) AS revenue_month',
                [$todayStartUtc],
            )
            ->first();

        return [
            $this->decimalString($metrics->revenue_today ?? 0),
            $this->decimalString($metrics->revenue_month ?? 0),
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
