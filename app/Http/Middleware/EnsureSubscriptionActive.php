<?php

namespace App\Http\Middleware;

use App\Enums\GymRole;
use App\Support\GymContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function __construct(private readonly GymContext $gymContext) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $subscription = $this->gymContext->gym()->subscription()->first();

        if ($subscription === null || $subscription->grantsAccess()) {
            return $next($request);
        }

        if ($this->gymContext->role() === GymRole::Owner) {
            return to_route('subscription.show');
        }

        abort(Response::HTTP_FORBIDDEN, 'Subscription akun Owner tidak aktif. Hubungi Owner gym.');
    }
}
