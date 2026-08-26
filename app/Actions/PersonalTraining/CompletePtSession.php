<?php

namespace App\Actions\PersonalTraining;

use App\Enums\MemberPtPackageStatus;
use App\Enums\PtSessionStatus;
use App\Models\MemberPtPackage;
use App\Models\PtSession;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompletePtSession
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function handle(PtSession $session): PtSession
    {
        return DB::transaction(function () use ($session): PtSession {
            $lockedSession = $this->gymContext->gym()->ptSessions()
                ->whereKey($session->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedSession->status !== PtSessionStatus::Scheduled
                || $lockedSession->quota_consumed
            ) {
                throw ValidationException::withMessages([
                    'session' => 'Sesi ini sudah diproses dan tidak dapat diselesaikan lagi.',
                ]);
            }

            $memberPtPackage = $this->lockedPackage($lockedSession);
            $this->consumeQuota($memberPtPackage);
            $lockedSession->update([
                'status' => PtSessionStatus::Completed,
                'completed_at' => now(),
                'quota_consumed' => true,
            ]);
            $this->activityLogger->record('pt_session.completed', $lockedSession, [
                'member_pt_package_id' => $memberPtPackage->getKey(),
                'used_sessions' => $memberPtPackage->used_sessions,
                'total_sessions' => $memberPtPackage->total_sessions,
            ]);

            return $lockedSession;
        }, 3);
    }

    private function lockedPackage(PtSession $session): MemberPtPackage
    {
        return $this->gymContext->gym()->memberPtPackages()
            ->whereKey($session->member_pt_package_id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function consumeQuota(MemberPtPackage $memberPtPackage): void
    {
        if ($memberPtPackage->used_sessions >= $memberPtPackage->total_sessions) {
            throw ValidationException::withMessages([
                'session' => 'Quota paket PT sudah habis.',
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
}
