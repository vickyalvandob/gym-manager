<?php

namespace App\Http\Controllers;

use App\Actions\PersonalTraining\CompletePtSession;
use App\Support\GymContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CompletePtSessionController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function __invoke(
        int $pt_session,
        CompletePtSession $completePtSession,
    ): RedirectResponse {
        $session = $this->gymContext->gym()->ptSessions()->whereKey($pt_session)->firstOrFail();
        Gate::authorize('complete', $session);
        $completePtSession->handle($session);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Sesi selesai dan quota PT telah diperbarui.',
        ]);

        return back();
    }
}
