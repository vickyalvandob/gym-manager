<?php

namespace App\Http\Controllers;

use App\Actions\Gyms\CreateSubscribedGym;
use App\Enums\GymRole;
use App\Http\Requests\StoreSubscribedGymRequest;
use App\Models\Gym;
use App\Models\Subscription;
use App\Models\User;
use App\Support\GymContext;
use App\Support\SubscriptionQuota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class SubscribedGymController extends Controller
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly SubscriptionQuota $subscriptionQuota,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($this->gymContext->role() === GymRole::Owner, HttpResponse::HTTP_FORBIDDEN);

        $subscriber = $request->user();
        assert($subscriber instanceof User);
        $subscription = $this->subscription($subscriber);
        $gyms = $subscription->gyms()
            ->select(['id', 'subscription_id', 'name', 'slug', 'status', 'onboarding_completed_at'])
            ->oldest('id')
            ->get()
            ->map(fn (Gym $gym): array => [
                'id' => $gym->getKey(),
                'name' => $gym->name,
                'slug' => $gym->slug,
                'status' => $gym->status->value,
                'status_label' => $gym->status->label(),
                'onboarding_completed' => $gym->onboarding_completed_at !== null,
                'is_current' => $gym->is($this->gymContext->gym()),
            ]);
        $usage = $this->subscriptionQuota->usage($subscription);

        return Inertia::render('gyms/index', [
            'gyms' => $gyms,
            'subscription' => [
                'plan_name' => $subscription->plan->name,
                'grants_access' => $subscription->grantsAccess(),
                'usage' => $usage,
                'limits' => [
                    'gyms' => $subscription->plan->max_gyms,
                    'members' => $subscription->plan->max_members,
                    'staff' => $subscription->plan->max_staff,
                ],
                'can_create_gym' => $subscription->grantsAccess()
                    && ($subscription->plan->max_gyms === null || $usage['gyms'] < $subscription->plan->max_gyms),
            ],
        ]);
    }

    public function store(
        StoreSubscribedGymRequest $request,
        CreateSubscribedGym $createSubscribedGym,
    ): RedirectResponse {
        $subscriber = $request->user();
        assert($subscriber instanceof User);
        $subscription = $this->subscription($subscriber);

        abort_unless($subscription->grantsAccess(), HttpResponse::HTTP_FORBIDDEN, 'Subscription tidak aktif.');

        $gym = $createSubscribedGym->handle($subscriber, $subscription, [
            'name' => $request->string('name')->toString(),
        ]);
        $request->session()->put('current_gym_id', $gym->getKey());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Gym {$gym->name} berhasil dibuat."]);

        return to_route('onboarding.edit');
    }

    private function subscription(User $subscriber): Subscription
    {
        $subscription = $this->gymContext->gym()->subscription()
            ->with('plan')
            ->first();

        abort_unless(
            $subscription instanceof Subscription
                && $subscription->subscriber_id === $subscriber->getKey(),
            HttpResponse::HTTP_FORBIDDEN,
            'Hanya pemilik subscription yang dapat mengelola multi-gym.',
        );

        return $subscription;
    }
}
