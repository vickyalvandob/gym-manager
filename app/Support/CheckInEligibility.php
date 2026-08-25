<?php

namespace App\Support;

use App\Enums\MemberStatus;
use App\Models\CheckIn;
use App\Models\Member;
use App\Models\MemberMembership;
use Carbon\CarbonImmutable;

class CheckInEligibility
{
    public const DuplicateWindowMinutes = 5;

    /**
     * @return array{
     *     can_check_in: bool,
     *     reason: string|null,
     *     latest_check_in_at: string|null,
     *     next_allowed_at: string|null
     * }
     */
    public function evaluate(
        Member $member,
        ?MemberMembership $activeMembership,
        ?CheckIn $latestCheckIn,
        CarbonImmutable $now,
    ): array {
        if ($member->status !== MemberStatus::Active) {
            return $this->result(
                false,
                'Member sedang nonaktif. Aktifkan member sebelum check-in.',
                $latestCheckIn,
            );
        }

        if (! $activeMembership instanceof MemberMembership) {
            return $this->result(
                false,
                'Membership tidak aktif pada hari ini. Tetapkan atau perpanjang membership terlebih dahulu.',
                $latestCheckIn,
            );
        }

        $nextAllowedAt = $latestCheckIn?->checked_in_at
            ?->toImmutable()
            ->addMinutes(self::DuplicateWindowMinutes);

        if ($nextAllowedAt !== null && $nextAllowedAt->isAfter($now)) {
            return [
                'can_check_in' => false,
                'reason' => 'Check-in baru saja dicatat. Tunggu beberapa menit sebelum mencatat ulang.',
                'latest_check_in_at' => $latestCheckIn->checked_in_at->toIso8601String(),
                'next_allowed_at' => $nextAllowedAt->toIso8601String(),
            ];
        }

        return $this->result(true, null, $latestCheckIn);
    }

    /**
     * @return array{
     *     can_check_in: bool,
     *     reason: string|null,
     *     latest_check_in_at: string|null,
     *     next_allowed_at: string|null
     * }
     */
    private function result(bool $canCheckIn, ?string $reason, ?CheckIn $latestCheckIn): array
    {
        return [
            'can_check_in' => $canCheckIn,
            'reason' => $reason,
            'latest_check_in_at' => $latestCheckIn?->checked_in_at?->toIso8601String(),
            'next_allowed_at' => null,
        ];
    }
}
