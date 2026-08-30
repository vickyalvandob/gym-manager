<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\SaasPlanInterval;
use App\Enums\SubscriptionStatus;
use App\Models\ActivityLog;
use App\Models\Gym;
use App\Models\PlatformActivityLog;
use App\Models\SaasPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private readonly Request $request) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'gym_name' => ['required', 'string', 'max:120'],
            'saas_plan_id' => [
                'required',
                'integer',
                Rule::exists(SaasPlan::class, 'id')->where('is_active', true),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $plan = SaasPlan::query()
                ->where('is_active', true)
                ->findOrFail((int) $input['saas_plan_id']);
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $gym = Gym::create([
                'name' => $input['gym_name'],
                'slug' => $this->uniqueSlug($input['gym_name']),
            ]);

            $gym->users()->attach($user, [
                'role' => GymRole::Owner->value,
                'status' => GymUserStatus::Active->value,
            ]);

            $startedAt = now();
            $isTrial = $plan->trial_days > 0;
            $isFreePlan = (float) $plan->price === 0.0;
            $subscription = $user->subscription()->create([
                'saas_plan_id' => $plan->getKey(),
                'status' => $isTrial ? SubscriptionStatus::Trialing : SubscriptionStatus::Active,
                'started_at' => $startedAt,
                'trial_ends_at' => $isTrial ? $startedAt->copy()->addDays($plan->trial_days) : null,
                'current_period_starts_at' => $isTrial ? null : $startedAt,
                'current_period_ends_at' => $isTrial
                    ? null
                    : ($isFreePlan ? null : match ($plan->billing_interval) {
                        SaasPlanInterval::Monthly => $startedAt->copy()->addMonthNoOverflow(),
                        SaasPlanInterval::Yearly => $startedAt->copy()->addYearNoOverflow(),
                    }),
            ]);
            $gym->forceFill(['subscription_id' => $subscription->getKey()])->save();

            ActivityLog::create([
                'gym_id' => $gym->getKey(),
                'user_id' => $user->getKey(),
                'event' => 'user.created',
                'subject_type' => User::class,
                'subject_id' => $user->getKey(),
                'properties' => ['source' => 'registration'],
                'ip_address' => $this->request->ip(),
            ]);

            PlatformActivityLog::query()->create([
                'actor_id' => $user->getKey(),
                'event' => 'gym.registered',
                'subject_type' => $gym->getMorphClass(),
                'subject_id' => $gym->getKey(),
                'properties' => [
                    'source' => 'self_service_registration',
                    'plan' => $plan->name,
                    'trial_days' => $plan->trial_days,
                ],
                'ip_address' => $this->request->ip(),
            ]);

            return $user;
        });
    }

    private function uniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'gym';

        return Str::limit($baseSlug, 120, '').'-'.Str::lower(Str::random(6));
    }
}
