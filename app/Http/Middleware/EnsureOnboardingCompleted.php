<?php

namespace App\Http\Middleware;

use App\Enums\GymRole;
use App\Support\GymContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    public function __construct(private readonly GymContext $gymContext) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->gymContext->gym()->onboarding_completed_at === null) {
            if ($this->gymContext->role() === GymRole::Owner) {
                return to_route('onboarding.edit');
            }

            abort(Response::HTTP_FORBIDDEN, 'Onboarding gym belum diselesaikan oleh Owner.');
        }

        return $next($request);
    }
}
