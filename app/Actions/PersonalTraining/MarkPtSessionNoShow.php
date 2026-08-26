<?php

namespace App\Actions\PersonalTraining;

use App\Enums\MemberPtPackageStatus;
use App\Enums\PtSessionStatus;
use App\Models\PtSession;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkPtSessionNoShow
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function handle(PtSession $session): PtSession
    {
        return DB::transaction(function () use ($session): PtSession {
            $gym = $this->gymContext->gym();
            $lockedSession = $gym->ptSessions()
                ->whereKey($session->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSession->status !== PtSessionStatus::Scheduled) {
                throw ValidationException::withMessages([
                    'session' => 'Sesi ini sudah diproses dan tidak dapat ditandai ulang.',
                ]);
            }

            $memberPtPackage = $gym->memberPtPackages()
                ->whereKey($lockedSession->member_pt_package_id)
                ->lockForUpdate()
                ->firstOrFail();
            $consumeQuota = $gym->count_pt_no_show_as_used_session;

            if ($consumeQuota) {
                if (
                    $lockedSession->quota_consumed
                    || $memberPtPackage->used_sessions >= $memberPtPackage->total_sessions
                ) {
                    throw ValidationException::withMessages([
                        'session' => 'Quota sesi ini sudah diproses.',
                    ]);
                }

                $usedSessions = $memberPtPackage->used_sessions + 1;
                $memberPtPackage->update([
                    'used_sessions' => $usedSessions,
                    'status' => $usedSessions >= $memberPtPackage->total_sessions
                        ? MemberPtPackageStatus::Completed
                        : $memberPtPackage->status,
                ]);
            }

            $lockedSession->update([
                'status' => PtSessionStatus::NoShow,
                'quota_consumed' => $consumeQuota,
            ]);
            $this->activityLogger->record('pt_session.no_show', $lockedSession, [
                'member_pt_package_id' => $memberPtPackage->getKey(),
                'quota_consumed' => $consumeQuota,
                'used_sessions' => $memberPtPackage->used_sessions,
            ]);

            return $lockedSession;
        }, 3);
    }
}
