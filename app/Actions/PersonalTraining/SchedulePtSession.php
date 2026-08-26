<?php

namespace App\Actions\PersonalTraining;

use App\Enums\PtSessionStatus;
use App\Models\MemberPtPackage;
use App\Models\PtSession;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use App\Support\PtSessionScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class SchedulePtSession
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly PtSessionScheduler $scheduler,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function handle(
        MemberPtPackage $memberPtPackage,
        CarbonImmutable $scheduledAtUtc,
        int $durationMinutes,
        ?string $notes,
        User $createdBy,
    ): PtSession {
        return DB::transaction(function () use (
            $memberPtPackage,
            $scheduledAtUtc,
            $durationMinutes,
            $notes,
            $createdBy,
        ): PtSession {
            $lockedPackage = $this->gymContext->gym()->memberPtPackages()
                ->whereKey($memberPtPackage->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $trainer = $this->gymContext->gym()->trainers()
                ->whereKey($lockedPackage->trainer_id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->scheduler->validate(
                $lockedPackage,
                $trainer,
                $scheduledAtUtc,
                $durationMinutes,
            );
            $session = $this->gymContext->gym()->ptSessions()->create([
                'member_pt_package_id' => $lockedPackage->getKey(),
                'member_id' => $lockedPackage->member_id,
                'trainer_id' => $trainer->getKey(),
                'scheduled_at' => $scheduledAtUtc,
                'duration_minutes' => $durationMinutes,
                'status' => PtSessionStatus::Scheduled,
                'notes' => $notes,
                'created_by' => $createdBy->getKey(),
            ]);

            $this->activityLogger->record('pt_session.created', $session, [
                'member_id' => $session->member_id,
                'trainer_id' => $session->trainer_id,
                'member_pt_package_id' => $session->member_pt_package_id,
                'scheduled_at' => $session->scheduled_at->toIso8601String(),
                'duration_minutes' => $session->duration_minutes,
            ]);

            return $session;
        }, 3);
    }
}
