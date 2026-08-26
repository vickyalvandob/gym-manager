<?php

namespace App\Http\Controllers;

use App\Actions\PersonalTraining\MarkPtSessionNoShow;
use App\Support\GymContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class MarkPtSessionNoShowController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function __invoke(
        int $pt_session,
        MarkPtSessionNoShow $markPtSessionNoShow,
    ): RedirectResponse {
        $session = $this->gymContext->gym()->ptSessions()->whereKey($pt_session)->firstOrFail();
        Gate::authorize('markNoShow', $session);
        $session = $markPtSessionNoShow->handle($session);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $session->quota_consumed
                ? 'No-show dicatat dan quota PT dikurangi.'
                : 'No-show dicatat tanpa mengurangi quota PT.',
        ]);

        return back();
    }
}
