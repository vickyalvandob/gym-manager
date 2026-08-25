<?php

namespace App\Actions\CheckIns;

use App\Models\CheckIn;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\CheckInEligibility;
use App\Support\GymContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordMemberCheckIn
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
        private readonly CheckInEligibility $checkInEligibility,
    ) {}

    public function handle(Member $member, User $createdBy): CheckIn
    {
        return DB::transaction(function () use ($member, $createdBy): CheckIn {
            $gym = $this->gymContext->gym();
            $now = CarbonImmutable::now('UTC');
            $localDate = $now->setTimezone($gym->timezone)->toDateString();
            $lockedMember = $gym->members()
                ->whereKey($member->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $activeMembership = $lockedMember->memberships()
                ->where('gym_id', $gym->getKey())
                ->whereDate('start_date', '<=', $localDate)
                ->whereDate('end_date', '>=', $localDate)
                ->latest('start_date')
                ->latest('id')
                ->lockForUpdate()
                ->first();
            $latestCheckIn = $lockedMember->checkIns()
                ->where('gym_id', $gym->getKey())
                ->latest('checked_in_at')
                ->latest('id')
                ->first();
            $eligibility = $this->checkInEligibility->evaluate(
                $lockedMember,
                $activeMembership,
                $latestCheckIn,
                $now,
            );

            if (! $eligibility['can_check_in']) {
                throw ValidationException::withMessages([
                    'check_in' => $eligibility['reason'] ?? 'Check-in tidak dapat dicatat.',
                ]);
            }

            if (! $activeMembership instanceof MemberMembership) {
                throw ValidationException::withMessages([
                    'check_in' => 'Membership aktif tidak ditemukan.',
                ]);
            }

            $checkIn = $lockedMember->checkIns()->create([
                'gym_id' => $gym->getKey(),
                'member_membership_id' => $activeMembership->getKey(),
                'checked_in_at' => $now,
                'created_by' => $createdBy->getKey(),
            ]);

            $this->activityLogger->record('checkin.created', $checkIn, [
                'member_id' => $lockedMember->getKey(),
                'member_membership_id' => $activeMembership->getKey(),
                'checked_in_at' => $now->toIso8601String(),
                'created_by' => $createdBy->getKey(),
            ]);

            return $checkIn;
        }, 3);
    }
}
