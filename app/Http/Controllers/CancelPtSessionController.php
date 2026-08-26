<?php

namespace App\Http\Controllers;

use App\Actions\PersonalTraining\CancelPtSession;
use App\Http\Requests\CancelPtSessionRequest;
use App\Support\GymContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CancelPtSessionController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function __invoke(
        CancelPtSessionRequest $request,
        int $pt_session,
        CancelPtSession $cancelPtSession,
    ): RedirectResponse {
        $session = $this->gymContext->gym()->ptSessions()->whereKey($pt_session)->firstOrFail();
        Gate::authorize('update', $session);
        $cancelPtSession->handle(
            $session,
            is_string($request->validated('cancellation_reason'))
                ? $request->validated('cancellation_reason')
                : null,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Sesi PT dibatalkan tanpa mengurangi quota.',
        ]);

        return back();
    }
}
