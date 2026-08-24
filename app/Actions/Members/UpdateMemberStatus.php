<?php

namespace App\Actions\Members;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;

class UpdateMemberStatus
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function handle(Member $member, MemberStatus $status): Member
    {
        if ($member->status === $status) {
            return $member;
        }

        return DB::transaction(function () use ($member, $status): Member {
            $previousStatus = $member->status;
            $member->update(['status' => $status]);

            $this->activityLogger->record('member.status_changed', $member, [
                'member_number' => $member->member_number,
                'from' => $previousStatus->value,
                'to' => $status->value,
            ]);

            return $member;
        }, 3);
    }
}
