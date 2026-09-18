<?php

namespace App\Http\Middleware;

use App\Models\Member;
use App\Support\MemberPortalAccess;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ResolveMemberWorkspace
{
    public function __construct(private readonly MemberPortalAccess $memberPortalAccess) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user !== null, 403);
        $selectedId = $request->query('profile', $request->session()->get('member_profile_id'));
        $member = $user->memberProfiles()->with('gym.subscription')
            ->when($selectedId !== null, fn ($query) => $query->whereKey($selectedId))
            ->oldest('id')->first();
        abort_unless($member instanceof Member, 403);

        if (! $this->memberPortalAccess->allows($user, $member)) {
            return Inertia::render('errors/unavailable')->toResponse($request)->setStatusCode(503);
        }

        $request->session()->put('member_profile_id', $member->getKey());
        $request->route()->setParameter('member', $member);

        return $next($request);
    }
}
