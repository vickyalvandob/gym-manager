<?php

namespace App\Http\Controllers;

use App\Support\DashboardSnapshot;
use App\Support\GymContext;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly DashboardSnapshot $dashboardSnapshot,
    ) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(): Response
    {
        Gate::authorize('view', $this->gymContext->gym());

        return Inertia::render('dashboard', [
            'snapshot' => Inertia::defer(
                fn (): array => $this->dashboardSnapshot->make(),
                'dashboard',
                rescue: true,
            ),
        ]);
    }
}
