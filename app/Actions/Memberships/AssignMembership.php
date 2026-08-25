<?php

namespace App\Actions\Memberships;

use App\Actions\Payments\CreateMembershipPayment;
use App\Enums\MembershipDurationUnit;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignMembership
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
        private readonly CreateMembershipPayment $createMembershipPayment,
    ) {}

    public function handle(
        Member $member,
        MembershipPlan $membershipPlan,
        string $startDate,
        ?MemberMembership $renewedFrom = null,
    ): MemberMembership {
        return DB::transaction(function () use ($member, $membershipPlan, $startDate, $renewedFrom): MemberMembership {
            $lockedGym = Gym::query()
                ->whereKey($this->gymContext->gymId())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedMember = $lockedGym->members()
                ->whereKey($member->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedPlan = $lockedGym->membershipPlans()
                ->whereKey($membershipPlan->getKey())
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();
            $periodStart = CarbonImmutable::parse(
                $startDate,
                $lockedGym->timezone,
            )->startOfDay();
            $periodEnd = $this->calculateEndDate(
                $periodStart,
                $lockedPlan->duration,
                $lockedPlan->duration_unit,
            );

            $lockedRenewedFrom = $renewedFrom === null
                ? null
                : $lockedMember->memberships()
                    ->where('gym_id', $this->gymContext->gymId())
                    ->whereKey($renewedFrom->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

            $renewedFromEndDate = $lockedRenewedFrom === null
                ? null
                : CarbonImmutable::parse(
                    $lockedRenewedFrom->end_date->toDateString(),
                    $lockedGym->timezone,
                )->startOfDay();

            if ($renewedFromEndDate !== null && ! $periodStart->isAfter($renewedFromEndDate)) {
                throw ValidationException::withMessages([
                    'start_date' => 'Tanggal mulai renewal harus setelah membership sebelumnya berakhir.',
                ]);
            }

            $hasOverlap = $lockedMember->memberships()
                ->where('gym_id', $this->gymContext->gymId())
                ->whereDate('start_date', '<=', $periodEnd->toDateString())
                ->whereDate('end_date', '>=', $periodStart->toDateString())
                ->exists();

            if ($hasOverlap) {
                throw ValidationException::withMessages([
                    'start_date' => 'Periode membership bertabrakan dengan riwayat membership yang sudah ada.',
                ]);
            }

            $memberMembership = $lockedMember->memberships()->create([
                'gym_id' => $this->gymContext->gymId(),
                'membership_plan_id' => $lockedPlan->getKey(),
                'renewed_from_id' => $lockedRenewedFrom?->getKey(),
                'plan_name' => $lockedPlan->name,
                'duration' => $lockedPlan->duration,
                'duration_unit' => $lockedPlan->duration_unit,
                'price' => $lockedPlan->price,
                'start_date' => $periodStart->toDateString(),
                'end_date' => $periodEnd->toDateString(),
            ]);

            $this->activityLogger->record(
                $lockedRenewedFrom === null ? 'membership.assigned' : 'membership.renewed',
                $memberMembership,
                [
                    'member_id' => $lockedMember->getKey(),
                    'member_number' => $lockedMember->member_number,
                    'membership_plan_id' => $lockedPlan->getKey(),
                    'plan_name' => $lockedPlan->name,
                    'start_date' => $periodStart->toDateString(),
                    'end_date' => $periodEnd->toDateString(),
                    'renewed_from_id' => $lockedRenewedFrom?->getKey(),
                ],
            );

            $this->createMembershipPayment->handle($memberMembership);

            return $memberMembership->load('payment');
        }, 3);
    }

    private function calculateEndDate(
        CarbonImmutable $startDate,
        int $duration,
        MembershipDurationUnit $durationUnit,
    ): CarbonImmutable {
        $exclusiveEnd = match ($durationUnit) {
            MembershipDurationUnit::Day => $startDate->addDays($duration),
            MembershipDurationUnit::Week => $startDate->addWeeks($duration),
            MembershipDurationUnit::Month => $startDate->addMonthsNoOverflow($duration),
            MembershipDurationUnit::Year => $startDate->addYearsNoOverflow($duration),
        };

        return $exclusiveEnd->subDay();
    }
}
