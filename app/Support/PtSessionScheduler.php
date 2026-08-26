<?php

namespace App\Support;

use App\Enums\MemberPtPackageStatus;
use App\Enums\PtSessionStatus;
use App\Enums\TrainerStatus;
use App\Models\MemberPtPackage;
use App\Models\PtSession;
use App\Models\Trainer;
use App\Models\TrainerMember;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class PtSessionScheduler
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function validate(
        MemberPtPackage $memberPtPackage,
        Trainer $trainer,
        CarbonImmutable $scheduledAtUtc,
        int $durationMinutes,
        ?PtSession $ignoredSession = null,
    ): void {
        $gym = $this->gymContext->gym();
        $scheduledAtLocal = $scheduledAtUtc->setTimezone($gym->timezone);
        $today = CarbonImmutable::now($gym->timezone)->startOfDay();

        if ($trainer->gym_id !== $gym->getKey() || $trainer->status !== TrainerStatus::Active) {
            $this->fail('trainer', 'Trainer aktif tidak ditemukan pada gym ini.');
        }

        if (
            $memberPtPackage->gym_id !== $gym->getKey()
            || $memberPtPackage->trainer_id !== $trainer->getKey()
        ) {
            $this->fail('member_pt_package_id', 'Paket PT tidak sesuai dengan trainer.');
        }

        if ($memberPtPackage->effectiveStatusOn($today) !== MemberPtPackageStatus::Active) {
            $this->fail('member_pt_package_id', 'Paket PT tidak aktif atau sudah kedaluwarsa.');
        }

        if ($scheduledAtLocal->lessThanOrEqualTo(CarbonImmutable::now($gym->timezone))) {
            $this->fail('date', 'Jadwal PT harus berada di waktu mendatang.');
        }

        if ($scheduledAtLocal->toDateString() < $memberPtPackage->start_date->toDateString()) {
            $this->fail('date', 'Jadwal tidak boleh sebelum tanggal mulai paket PT.');
        }

        if (
            $memberPtPackage->expires_at !== null
            && $scheduledAtLocal->toDateString() > $memberPtPackage->expires_at->toDateString()
        ) {
            $this->fail('date', 'Jadwal tidak boleh melewati masa berlaku paket PT.');
        }

        $hasAssignment = TrainerMember::query()
            ->where('gym_id', $gym->getKey())
            ->where('trainer_id', $trainer->getKey())
            ->where('member_id', $memberPtPackage->member_id)
            ->where('is_active', true)
            ->exists();

        if (! $hasAssignment) {
            $this->fail('member_pt_package_id', 'Member tidak lagi ditugaskan kepada trainer ini.');
        }

        $scheduledSessions = $memberPtPackage->sessions()
            ->where('status', PtSessionStatus::Scheduled->value)
            ->when(
                $ignoredSession !== null,
                fn ($query) => $query->whereKeyNot($ignoredSession->getKey()),
            )
            ->count();

        if ($memberPtPackage->availableSessions($scheduledSessions) < 1) {
            $this->fail('member_pt_package_id', 'Tidak ada sesi PT yang tersedia untuk dijadwalkan.');
        }

        $endsAtUtc = $scheduledAtUtc->addMinutes($durationMinutes);
        $conflictingSessions = $gym->ptSessions()
            ->select(['pt_sessions.id', 'pt_sessions.scheduled_at', 'pt_sessions.duration_minutes'])
            ->where('trainer_id', $trainer->getKey())
            ->where('status', PtSessionStatus::Scheduled->value)
            ->where('scheduled_at', '<', $endsAtUtc)
            ->when(
                $ignoredSession !== null,
                fn ($query) => $query->whereKeyNot($ignoredSession->getKey()),
            )
            ->get();

        $hasConflict = $conflictingSessions->contains(
            fn (PtSession $session): bool => $session->scheduled_at
                ->addMinutes($session->duration_minutes)
                ->greaterThan($scheduledAtUtc),
        );

        if ($hasConflict) {
            $this->fail('start_time', 'Anda sudah memiliki sesi lain pada waktu tersebut.');
        }
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
