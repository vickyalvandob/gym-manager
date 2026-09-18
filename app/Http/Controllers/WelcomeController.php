<?php

namespace App\Http\Controllers;

use App\Models\SaasPlan;
use App\Support\WorkspaceDestination;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, WorkspaceDestination $destination): Response
    {
        return Inertia::render('welcome', [
            'workspace' => $request->user() === null ? null : $destination->routeName(
                $request->user(), (int) $request->session()->get('current_gym_id', 0),
            ),
            'canChoosePlan' => $request->user()?->subscription()->exists() ?? false,
            'plans' => SaasPlan::query()
                ->where('is_active', true)
                ->where('price', '>', 0)
                ->orderBy('sort_order')->orderBy('id')
                ->get(['id', 'name', 'description', 'price', 'currency', 'billing_interval', 'max_gyms', 'max_members', 'max_staff', 'max_trainers', 'max_membership_plans', 'max_pt_packages'])
                ->map(fn (SaasPlan $plan): array => [
                    'id' => $plan->getKey(),
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'price' => $plan->price,
                    'currency' => $plan->currency,
                    'billing_interval' => $plan->billing_interval->value,
                    'max_gyms' => $plan->max_gyms,
                    'max_members' => $plan->max_members,
                    'max_staff' => $plan->max_staff,
                    'max_trainers' => $plan->max_trainers,
                    'max_membership_plans' => $plan->max_membership_plans,
                    'max_pt_packages' => $plan->max_pt_packages,
                ]),
        ]);
    }
}
