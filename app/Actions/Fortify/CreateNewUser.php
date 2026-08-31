<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\GymRole;
use App\Enums\GymUserStatus;
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
use Illuminate\Validation\ValidationException;
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
            'password' => $this->passwordRules(),
        ])->validate();

        $freePlan = SaasPlan::query()
            ->where('slug', 'free')
            ->where('is_active', true)
            ->where('price', 0)
            ->first();

        if ($freePlan === null) {
            throw ValidationException::withMessages([
                'gym_name' => 'Pendaftaran sementara tidak tersedia. Paket Free belum dikonfigurasi.',
            ]);
        }

        return DB::transaction(function () use ($input, $freePlan): User {
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
            $subscription = $user->subscription()->create([
                'saas_plan_id' => $freePlan->getKey(),
                'status' => SubscriptionStatus::Active,
                'started_at' => $startedAt,
                'trial_ends_at' => null,
                'current_period_starts_at' => $startedAt,
                'current_period_ends_at' => null,
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
                    'plan' => $freePlan->name,
                    'trial_days' => 0,
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
