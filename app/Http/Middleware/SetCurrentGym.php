<?php

namespace App\Http\Middleware;

use App\Enums\GymRole;
use App\Enums\GymStatus;
use App\Enums\GymUserStatus;
use App\Models\Gym;
use App\Support\GymContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentGym
{
    public function __construct(private readonly GymContext $gymContext) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $mode = 'required'): Response
    {
        $user = $request->user();

        abort_unless($user !== null, Response::HTTP_UNAUTHORIZED);

        $availableGyms = $user->gyms()
            ->select([
                'gyms.id',
                'gyms.name',
                'gyms.slug',
                'gyms.status',
                'gyms.timezone',
                'gyms.currency',
                'gyms.membership_expiry_warning_days',
                'gyms.count_pt_no_show_as_used_session',
            ])
            ->where('gyms.status', GymStatus::Active->value)
            ->wherePivot('status', GymUserStatus::Active->value)
            ->oldest('gym_user.created_at');

        $selectedGymId = (int) $request->session()->get('current_gym_id', 0);
        $gym = $selectedGymId > 0
            ? (clone $availableGyms)->whereKey($selectedGymId)->first()
            : null;

        $gym ??= $availableGyms->first();

        if (! $gym instanceof Gym) {
            $request->session()->forget('current_gym_id');

            if ($mode === 'optional') {
                return $next($request);
            }

            abort(Response::HTTP_FORBIDDEN, 'Akun Anda belum terhubung ke gym aktif.');
        }

        $pivot = $gym->getRelation('pivot');
        $role = GymRole::from((string) $pivot->getAttribute('role'));

        $this->gymContext->set($gym, $role);
        $request->session()->put('current_gym_id', $gym->getKey());

        Context::add('gym_id', $gym->getKey());
        Context::add('gym_role', $role->value);

        return $next($request);
    }
}
