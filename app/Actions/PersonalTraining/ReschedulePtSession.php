<?php

namespace App\Actions\PersonalTraining;

use App\Enums\PtSessionStatus;
use App\Models\PtSession;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use App\Support\PtSessionScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReschedulePtSession
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly PtSessionScheduler $scheduler,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function handle(
        PtSession $session,
        CarbonImmutable $scheduledAtUtc,
        int $durationMinutes,
        ?string $notes,
    ): PtSession {
        return DB::transaction(function () use (
            $session,
            $scheduledAtUtc,
            $durationMinutes,
            $notes,
        ): PtSession {
            $lockedSession = $this->gymContext->gym()->ptSessions()
                ->whereKey($session->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSession->status !== PtSessionStatus::Scheduled) {
                throw ValidationException::withMessages([
                    'session' => 'Hanya sesi terjadwal yang dapat dijadwalkan ulang.',
                ]);
            }

            $memberPtPackage = $this->gymContext->gym()->memberPtPackages()
                ->whereKey($lockedSession->member_pt_package_id)
                ->lockForUpdate()
                ->firstOrFail();
            $trainer = $this->gymContext->gym()->trainers()
                ->whereKey($lockedSession->trainer_id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->scheduler->validate(
                $memberPtPackage,
                $trainer,
                $scheduledAtUtc,
                $durationMinutes,
                $lockedSession,
            );
            $before = [
                'scheduled_at' => $lockedSession->scheduled_at->toIso8601String(),
                'duration_minutes' => $lockedSession->duration_minutes,
            ];
            $lockedSession->update([
                'scheduled_at' => $scheduledAtUtc,
                'duration_minutes' => $durationMinutes,
                'notes' => $notes,
            ]);

            $this->activityLogger->record('pt_session.rescheduled', $lockedSession, [
                'before' => $before,
                'scheduled_at' => $lockedSession->scheduled_at->toIso8601String(),
                'duration_minutes' => $lockedSession->duration_minutes,
            ]);

            return $lockedSession;
        }, 3);
    }
}
