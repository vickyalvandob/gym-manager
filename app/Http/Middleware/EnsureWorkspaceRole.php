<?php

namespace App\Http\Middleware;

use App\Enums\GymRole;
use App\Enums\TrainerStatus;
use App\Support\GymContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceRole
{
    public function __construct(private readonly GymContext $gymContext) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $area): Response
    {
        $role = $this->gymContext->role();
        abort_unless(match ($area) {
            'management' => in_array($role, [GymRole::Owner, GymRole::Admin], true),
            'trainer' => $role === GymRole::Trainer,
            default => false,
        }, 403);

        if ($area === 'trainer') {
            abort_unless($this->gymContext->gym()->trainers()
                ->where('user_id', $request->user()?->getKey())
                ->where('status', TrainerStatus::Active)
                ->exists(), 403);
        }

        return $next($request);
    }
}
