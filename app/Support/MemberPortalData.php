<?php

namespace App\Support;

use App\Enums\MemberPtPackageStatus;
use App\Enums\PtSessionStatus;
use App\Models\CheckIn;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MemberPtPackage;
use App\Models\Payment;
use App\Models\PtSession;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

class MemberPortalData
{
    public function __construct(private readonly MemberQrCode $memberQrCode) {}

    /** @return array<string, mixed> */
    public function member(Member $member): array
    {
        $member->loadMissing(['gym', 'user']);

        return [
            'id' => $member->getKey(),
            'member_number' => $member->member_number,
            'name' => $member->name,
            'phone' => $member->phone,
            'email' => $member->email ?? $member->user->email,
            'address' => $member->address,
            'emergency_contact' => $member->emergency_contact,
            'status' => $member->status->value,
            'status_label' => $member->status->label(),
            'photo_url' => is_string($member->photo)
                ? route('member-portal.photo', $member)
                : null,
            'gym' => [
                'id' => $member->gym->getKey(),
                'name' => $member->gym->name,
                'slug' => $member->gym->slug,
                'timezone' => $member->gym->timezone,
                'currency' => $member->gym->currency,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function overview(Member $member): array
    {
        $member->loadMissing(['gym', 'user']);
        $today = CarbonImmutable::now($member->gym->timezone)->startOfDay();
        $todayDate = $today->toDateString();
        $activeMembership = $member->memberships()
            ->with('payment')
            ->where('gym_id', $member->gym_id)
            ->whereDate('start_date', '<=', $todayDate)
            ->whereDate('end_date', '>=', $todayDate)
            ->latest('start_date')
            ->latest('id')
            ->first();
        $upcomingMembership = $member->memberships()
            ->with('payment')
            ->where('gym_id', $member->gym_id)
            ->whereDate('start_date', '>', $todayDate)
            ->oldest('start_date')
            ->oldest('id')
            ->first();
        $latestMembership = $member->memberships()
            ->with('payment')
            ->where('gym_id', $member->gym_id)
            ->latest('end_date')
            ->latest('id')
            ->first();
        $membership = $activeMembership ?? $upcomingMembership ?? $latestMembership;
        $latestCheckIn = $member->checkIns()
            ->where('gym_id', $member->gym_id)
            ->latest('checked_in_at')
            ->latest('id')
            ->first();
        $activePtPackage = $member->ptPackages()
            ->with(['ptPackage:id,name', 'trainer:id,trainer_code,name,specialization'])
            ->withCount([
                'sessions as scheduled_sessions_count' => fn ($query) => $query
                    ->where('status', PtSessionStatus::Scheduled->value),
            ])
            ->where('gym_id', $member->gym_id)
            ->where('status', MemberPtPackageStatus::Active->value)
            ->whereDate('start_date', '<=', $todayDate)
            ->where(function ($query) use ($todayDate): void {
                $query->whereNull('expires_at')->orWhereDate('expires_at', '>=', $todayDate);
            })
            ->latest('id')
            ->first();

        return [
            'qr_code_data_url' => $this->memberQrCode->dataUrl($member),
            'membership' => $membership instanceof MemberMembership
                ? $this->membershipData(
                    $membership,
                    $today,
                    $member->gym->membership_expiry_warning_days,
                )
                : null,
            'latest_check_in_at' => $latestCheckIn?->checked_in_at?->toIso8601String(),
            'active_pt_package' => $activePtPackage instanceof MemberPtPackage
                ? $this->ptPackageData($activePtPackage, $today)
                : null,
        ];
    }

    /** @return LengthAwarePaginator<int, array<string, mixed>> */
    public function memberships(Member $member): LengthAwarePaginator
    {
        $member->loadMissing('gym');
        $today = CarbonImmutable::today($member->gym->timezone);

        return $member->memberships()->where('gym_id', $member->gym_id)
            ->with('payment')->latest('start_date')->latest('id')
            ->paginate(10)->withQueryString()
            ->through(fn (MemberMembership $membership): array => $this->membershipData(
                $membership, $today, $member->gym->membership_expiry_warning_days,
            ));
    }

    /** @return LengthAwarePaginator<int, array<string, mixed>> */
    public function checkIns(Member $member): LengthAwarePaginator
    {
        return $member->checkIns()
            ->select([
                'check_ins.id',
                'check_ins.member_id',
                'check_ins.member_membership_id',
                'check_ins.checked_in_at',
            ])
            ->with('memberMembership:id,plan_name,end_date')
            ->where('gym_id', $member->gym_id)
            ->latest('checked_in_at')
            ->latest('id')
            ->paginate(10, ['*'], 'check_in_page')
            ->withQueryString()
            ->through(fn (CheckIn $checkIn): array => $this->checkInData($checkIn));
    }

    /** @return LengthAwarePaginator<int, array<string, mixed>> */
    public function payments(Member $member): LengthAwarePaginator
    {
        return $member->payments()
            ->with([
                'memberMembership:id,plan_name',
                'memberPtPackage.ptPackage:id,name',
            ])
            ->where('gym_id', $member->gym_id)
            ->latest('id')
            ->paginate(10, ['*'], 'payment_page')
            ->withQueryString()
            ->through(fn (Payment $payment): array => $this->paymentData($payment));
    }

    /** @return LengthAwarePaginator<int, array<string, mixed>> */
    public function ptPackages(Member $member): LengthAwarePaginator
    {
        $member->loadMissing('gym');
        $today = CarbonImmutable::now($member->gym->timezone)->startOfDay();

        return $member->ptPackages()
            ->with(['ptPackage:id,name', 'trainer:id,trainer_code,name,specialization'])
            ->withCount([
                'sessions as scheduled_sessions_count' => fn ($query) => $query
                    ->where('status', PtSessionStatus::Scheduled->value),
            ])
            ->where('gym_id', $member->gym_id)
            ->latest('id')
            ->paginate(10, ['*'], 'pt_package_page')
            ->withQueryString()
            ->through(fn (MemberPtPackage $package): array => $this->ptPackageData($package, $today));
    }

    /** @return LengthAwarePaginator<int, array<string, mixed>> */
    public function ptSessions(Member $member): LengthAwarePaginator
    {
        return $member->ptSessions()
            ->with([
                'trainer:id,trainer_code,name,specialization',
                'memberPtPackage.ptPackage:id,name',
            ])
            ->where('gym_id', $member->gym_id)
            ->latest('scheduled_at')
            ->latest('id')
            ->paginate(10, ['*'], 'pt_session_page')
            ->withQueryString()
            ->through(fn (PtSession $session): array => $this->ptSessionData($session));
    }

    /** @return array<string, mixed> */
    private function checkInData(CheckIn $checkIn): array
    {
        return [
            'id' => $checkIn->getKey(),
            'checked_in_at' => $checkIn->checked_in_at->toIso8601String(),
            'membership' => [
                'plan_name' => $checkIn->memberMembership->plan_name,
                'end_date' => $checkIn->memberMembership->end_date->toDateString(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function paymentData(Payment $payment): array
    {
        $description = match (true) {
            $payment->member_membership_id !== null => $payment->memberMembership->plan_name,
            $payment->member_pt_package_id !== null => $payment->memberPtPackage->package_name,
            default => $payment->type->label(),
        };

        return [
            'id' => $payment->getKey(),
            'invoice_number' => $payment->invoice_number,
            'type' => $payment->type->value,
            'type_label' => $payment->type->label(),
            'description' => $description,
            'amount' => $payment->amount,
            'status' => $payment->status->value,
            'status_label' => $payment->status->label(),
            'method_label' => $payment->method?->label(),
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'created_at' => $payment->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function ptSessionData(PtSession $session): array
    {
        return [
            'id' => $session->getKey(),
            'scheduled_at' => $session->scheduled_at->toIso8601String(),
            'duration_minutes' => $session->duration_minutes,
            'status' => $session->status->value,
            'status_label' => $session->status->label(),
            'trainer' => [
                'name' => $session->trainer->name,
                'specialization' => $session->trainer->specialization,
            ],
            'pt_package_name' => $session->memberPtPackage->package_name,
        ];
    }

    /** @return array<string, mixed> */
    private function membershipData(
        MemberMembership $membership,
        CarbonImmutable $today,
        int $warningDays,
    ): array {
        $status = $membership->statusOn($today);
        $daysRemaining = $status->value === 'active'
            ? (int) $today->diffInDays($membership->end_date, absolute: true)
            : null;

        return [
            'id' => $membership->getKey(),
            'plan_name' => $membership->plan_name,
            'start_date' => $membership->start_date->toDateString(),
            'end_date' => $membership->end_date->toDateString(),
            'status' => $status->value,
            'status_label' => $status->label(),
            'days_remaining' => $daysRemaining,
            'is_expiring_soon' => $daysRemaining !== null
                && $daysRemaining <= $warningDays,
            'payment_status' => $membership->payment?->status->value,
            'payment_status_label' => $membership->payment?->status->label(),
        ];
    }

    /** @return array<string, mixed> */
    private function ptPackageData(MemberPtPackage $package, CarbonImmutable $today): array
    {
        $scheduledSessions = (int) ($package->getAttribute('scheduled_sessions_count') ?? 0);
        $status = $package->effectiveStatusOn($today);

        return [
            'id' => $package->getKey(),
            'name' => $package->package_name,
            'trainer' => [
                'name' => $package->trainer->name,
                'specialization' => $package->trainer->specialization,
            ],
            'total_sessions' => $package->total_sessions,
            'used_sessions' => $package->used_sessions,
            'scheduled_sessions' => $scheduledSessions,
            'available_sessions' => $package->availableSessions($scheduledSessions),
            'start_date' => $package->start_date->toDateString(),
            'expires_at' => $package->expires_at?->toDateString(),
            'status' => $status->value,
            'status_label' => $status->label(),
            'payment_status' => $package->payment_status->value,
            'payment_status_label' => $package->payment_status->label(),
        ];
    }
}
