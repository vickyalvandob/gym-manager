<?php

namespace App\Http\Controllers;

use App\Actions\PersonalTraining\ReschedulePtSession;
use App\Http\Requests\ReschedulePtSessionRequest;
use App\Support\GymContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ReschedulePtSessionController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function __invoke(
        ReschedulePtSessionRequest $request,
        int $pt_session,
        ReschedulePtSession $reschedulePtSession,
    ): RedirectResponse {
        $session = $this->gymContext->gym()->ptSessions()->whereKey($pt_session)->firstOrFail();
        Gate::authorize('update', $session);
        $reschedulePtSession->handle(
            $session,
            CarbonImmutable::parse(
                $request->validated('date').' '.$request->validated('start_time'),
                $this->gymContext->gym()->timezone,
            )->utc(),
            (int) $request->validated('duration_minutes'),
            is_string($request->validated('notes')) ? $request->validated('notes') : null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Jadwal sesi PT berhasil diperbarui.',
        ]);

        return back();
    }
}
