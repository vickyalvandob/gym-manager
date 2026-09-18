<?php

namespace App\Support;

use App\Enums\GymRole;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\PtPackage;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class SubscriptionQuota
{
    /** @return array{gyms: int, members: int, staff: int, trainers: int, membership_plans: int, pt_packages: int} */
    public function usage(Subscription $subscription): array
    {
        $subscriptionId = $subscription->getKey();

        return [
            'gyms' => $subscription->gyms()->count(),
            'members' => $this->memberUsage($subscriptionId),
            'staff' => $this->staffUsage($subscriptionId),
            'trainers' => $this->resourceUsage($subscription, Trainer::class),
            'membership_plans' => $this->resourceUsage($subscription, MembershipPlan::class),
            'pt_packages' => $this->resourceUsage($subscription, PtPackage::class),
        ];
    }

    public function ensureCanCreateGym(Subscription $subscription): void
    {
        $this->ensureBelowLimit('name', $subscription->gyms()->count(), $subscription->plan->max_gyms, 'Batas jumlah gym pada paket subscription sudah tercapai.');
    }

    public function ensureCanCreateMember(Subscription $subscription): void
    {
        $this->ensureBelowLimit('name', $this->memberUsage($subscription->getKey()), $subscription->plan->max_members, 'Batas member pada paket subscription sudah tercapai.');
    }

    public function ensureCanCreateStaff(Subscription $subscription): void
    {
        $this->ensureBelowLimit('email', $this->staffUsage($subscription->getKey()), $subscription->plan->max_staff, 'Batas staf pada paket subscription sudah tercapai.');
    }

    public function ensureCanCreateTrainer(Subscription $subscription): void
    {
        $this->ensureBelowLimit('name', $this->resourceUsage($subscription, Trainer::class), $subscription->plan->max_trainers, 'Batas trainer tercapai. Upgrade paket untuk menambah trainer.');
    }

    public function ensureCanCreateMembershipPlan(Subscription $subscription): void
    {
        $this->ensureBelowLimit('name', $this->resourceUsage($subscription, MembershipPlan::class), $subscription->plan->max_membership_plans, 'Batas paket membership tercapai. Upgrade paket untuk menambah paket.');
    }

    public function ensureCanCreatePtPackage(Subscription $subscription): void
    {
        $this->ensureBelowLimit('name', $this->resourceUsage($subscription, PtPackage::class), $subscription->plan->max_pt_packages, 'Batas paket PT tercapai. Upgrade paket untuk menambah paket.');
    }

    /** @param class-string<Model> $model */
    private function resourceUsage(Subscription $subscription, string $model): int
    {
        return $model::query()->whereIn('gym_id', $subscription->gyms()->select('gyms.id'))->count();
    }

    /** @return array{gyms: ?int, members: ?int, staff: ?int, trainers: ?int, membership_plans: ?int, pt_packages: ?int} */
    public function limits(SaasPlan $plan): array
    {
        return [
            'gyms' => $plan->max_gyms,
            'members' => $plan->max_members,
            'staff' => $plan->max_staff,
            'trainers' => $plan->max_trainers,
            'membership_plans' => $plan->max_membership_plans,
            'pt_packages' => $plan->max_pt_packages,
        ];
    }

    /** @param array<string, int> $usage */
    public function planCoversUsage(SaasPlan $plan, array $usage): bool
    {
        foreach ($this->limits($plan) as $resource => $limit) {
            if ($limit !== null && ($usage[$resource] ?? 0) > $limit) {
                return false;
            }
        }

        return true;
    }

    public function ensurePlanCoversUsage(
        Subscription $subscription,
        SaasPlan $plan,
        int $additionalGyms = 0,
        ?string $errorField = 'saas_plan_id',
    ): void {
        $usage = $this->usage($subscription);
        $usage['gyms'] += $additionalGyms;
        $errors = [];

        foreach ([
            ['field' => 'max_gyms', 'usage' => $usage['gyms'], 'limit' => $plan->max_gyms, 'label' => 'gym'],
            ['field' => 'max_members', 'usage' => $usage['members'], 'limit' => $plan->max_members, 'label' => 'member'],
            ['field' => 'max_staff', 'usage' => $usage['staff'], 'limit' => $plan->max_staff, 'label' => 'staf'],
            ['field' => 'max_trainers', 'usage' => $usage['trainers'], 'limit' => $plan->max_trainers, 'label' => 'trainer'],
            ['field' => 'max_membership_plans', 'usage' => $usage['membership_plans'], 'limit' => $plan->max_membership_plans, 'label' => 'paket membership'],
            ['field' => 'max_pt_packages', 'usage' => $usage['pt_packages'], 'limit' => $plan->max_pt_packages, 'label' => 'paket PT'],
        ] as $capacity) {
            if ($capacity['limit'] !== null && $capacity['usage'] > $capacity['limit']) {
                $field = $errorField ?? $capacity['field'];
                $errors[$field][] = "Pemakaian {$capacity['label']} saat ini ({$capacity['usage']}) melebihi batas paket ({$capacity['limit']}).";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function ensureBelowLimit(string $field, int $usage, ?int $limit, string $message): void
    {
        if ($limit !== null && $usage >= $limit) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }

    private function memberUsage(int $subscriptionId): int
    {
        return Member::query()
            ->whereHas('gym', fn ($query) => $query->where('subscription_id', $subscriptionId))
            ->count();
    }

    private function staffUsage(int $subscriptionId): int
    {
        return User::query()
            ->whereHas('gyms', fn ($query) => $query
                ->where('gyms.subscription_id', $subscriptionId)
                ->where('gym_user.role', '!=', GymRole::Owner->value))
            ->count();
    }
}
